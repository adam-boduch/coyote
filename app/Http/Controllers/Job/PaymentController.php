<?php

namespace Coyote\Http\Controllers\Job;

use Coyote\Events\PaymentPaid;
use Coyote\Http\Controllers\Controller;
use Coyote\Http\Forms\Job\PaymentForm;
use Coyote\Payment;
use Coyote\Repositories\Contracts\CurrencyRepositoryInterface as CurrencyRepository;
use Coyote\Services\Cardinity\Client as Cardinity;
use Coyote\Services\Cardinity\Exceptions\Declined;
use Coyote\Services\Cardinity\Exceptions\Forbidden;
use Coyote\Services\Cardinity\Exceptions\InternalServerError;
use Coyote\Services\Cardinity\Exceptions\ServiceUnavailable;
use Coyote\Services\Cardinity\Exceptions\ValidationFailed;
use Coyote\Services\Cardinity\Exceptions\Unauthorized;
use Coyote\Services\Cardinity\Payment\Create as PaymentCreate;
use Coyote\Services\UrlBuilder\UrlBuilder;
use Coyote\Services\Invoice\Generator as InvoiceGenerator;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * @var CurrencyRepository
     */
    private $currency;

    /**
     * @var InvoiceGenerator
     */
    private $invoice;

    /**
     * @param CurrencyRepository $currency
     * @param InvoiceGenerator $invoice
     */
    public function __construct(CurrencyRepository $currency, InvoiceGenerator $invoice)
    {
        parent::__construct();

        $this->currency = $currency;
        $this->invoice = $invoice;

        $this->middleware(function (Request $request, $next) {
            /** @var \Coyote\Payment $payment */
            $payment = $request->route('payment');

            abort_if($payment->status_id == Payment::PAID, 404);

            return $next($request);
        });
    }

    /**
     * @param \Coyote\Payment $payment
     * @return \Illuminate\View\View
     */
    public function index($payment)
    {
        $this->breadcrumb->push('Praca', route('job.home'));
        $this->breadcrumb->push($payment->job->title, UrlBuilder::job($payment->job));
        $this->breadcrumb->push('Promowanie ogłoszenia');

        return $this->view('job.payment', [
            'form'              => $this->getForm($payment),
            'payment'           => $payment,
            'exchange_rate'     => $this->currency->latest('EUR')
        ]);
    }

    /**
     * @param \Coyote\Payment $payment
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function process($payment)
    {
        $form = $this->getForm($payment);
        $form->validate();

        return $this->handlePayment(function () use ($payment, $form) {
            $result = Cardinity::create()->createPayment([
                'amount'            => round($payment->grossPrice() * $this->currency->latest('EUR'), 2),
                'currency'          => 'EUR',
                'settle'            => true,
                'order_id'          => $payment->id,
                'country'           => 'PL',
                'payment_method'    => PaymentCreate::CARD,
//                'description'       => '3d-pass',
                'payment_instrument'=> [
                    'pan'           => $form->get('number')->getValue(),
                    'exp_year'      => $form->get('exp_year')->getValue(),
                    'exp_month'     => $form->get('exp_month')->getValue(),
                    'cvc'           => $form->get('cvc')->getValue(),
                    'holder'        => $form->get('name')->getValue()
                ],
            ]);

            // save invoice data. keep in mind that we do not setup invoice number until payment is done.
            /** @var \Coyote\Invoice $invoice */
            $invoice = $this->invoice->create(
                array_merge($form->all()['invoice'], ['user_id' => $this->auth->id]),
                $payment
            );

            // associate invoice with payment
            $payment->invoice()->associate($invoice);
            $payment->save();

            if ($result->isPending()) {
                return view('job.3dsecure', [
                    'uuid'          => $result->id,
                    'callback_url'  => route('job.payment.callback', [$payment->id]),
                    'url'           => $result->authorizationInformation->url,
                    'data'          => $result->authorizationInformation->data
                ]);
            }

            logger()->debug('Successfully payment', ['uuid' => $result->id]);

            // boost job offer, send invoice and reindex
            event(new PaymentPaid($payment, $this->auth));

            return redirect()
                ->to(UrlBuilder::job($payment->job))
                ->with('success', trans('payment.success'));
        });
    }

    /**
     * @param \Coyote\Payment $payment
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function callback($payment, Request $request)
    {
        return $this->handlePayment(function () use ($payment, $request) {
            Cardinity::create()->finalizePayment($request->input('MD'), $request->input('PaRes'));

            // boost job offer, send invoice and reindex
            event(new PaymentPaid($payment, $this->auth));

            return redirect()
                ->to(UrlBuilder::job($payment->job))
                ->with('success', trans('payment.success'));
        });
    }

    /**
     * @param \Coyote\Payment $payment
     * @return \Coyote\Services\FormBuilder\Form
     */
    private function getForm($payment)
    {
        return $this->createForm(PaymentForm::class, $payment);
    }

    /**
     * @param \Exception $exception
     * @param $translationId
     * @return \Illuminate\Http\RedirectResponse
     */
    private function handlePaymentException($exception, $translationId)
    {
        if (!($exception instanceof ValidationFailed) && !($exception instanceof Declined)) {
            // log error without credit card number etc.
            logger()->error($exception);

            if (app()->environment('production')) {
                // sentry will cut off credit card number
                app('sentry')->captureException($exception);
            }
        }

        return back()->withInput()->with('error', trans($translationId));
    }

    /**
     * @param \Closure $callback
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    private function handlePayment(\Closure $callback)
    {
        try {
            return $callback();
        } catch (Forbidden $e) {
            return $this->handlePaymentException($e, 'payment.forbidden');
        } catch (InternalServerError $e) {
            return $this->handlePaymentException($e, 'payment.internal_server');
        } catch (ServiceUnavailable $e) {
            return $this->handlePaymentException($e, 'payment.service_unavailable');
        } catch (Unauthorized $e) {
            return $this->handlePaymentException($e, 'payment.unauthorized');
        } catch (ValidationFailed $e) {
            return $this->handlePaymentException($e, 'payment.validation');
        } catch (Declined $e) {
            return $this->handlePaymentException($e, 'payment.declined');
        } catch (\Exception $e) {
            return $this->handlePaymentException($e, 'payment.unhandled');
        }
    }
}
