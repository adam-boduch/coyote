<?php

namespace Coyote\Http\Controllers\User;

use Coyote\Events\UserDeleted;
use Coyote\Rules\PasswordCheck;
use Coyote\Services\Stream\Activities\Delete;
use Coyote\Services\Stream\Objects\Person;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeleteAccountController extends BaseController
{
    use AuthenticatesUsers{
        logout as traitLogout;
    }

    use HomeTrait;

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return $this->view('user.delete');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function delete(Request $request, Guard $guard)
    {
        $this->validate($request, [
            'password' => [
                'bail',
                Rule::requiredIf(function () {
                    return $this->auth->password !== null;
                }),
                app(PasswordCheck::class)
            ]
        ]);

        $this->auth->timestamps = false;
        $this->auth->forceFill([
            'ip'        => $request->ip(),
            'browser'   => $request->browser(),
            'visits'    => $this->auth->visits + 1,
            'visited_at'=> now(),
            'is_online' => false
        ]);

        $this->transaction(function () use ($request, $guard) {
            $this->auth->save();

            // save information before logging out
            stream(Delete::class, new Person($this->auth));

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $guard->logout();
            $this->auth->delete();

            event(new UserDeleted($this->auth));
        });

        $request->session()->flash('success', 'Konto zostało prawidłowo usunięte.');

        return redirect()->to('/');
    }
}
