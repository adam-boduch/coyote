<?php

namespace Coyote\Notifications\Microblog;

use Coyote\Microblog;
use Coyote\User;
use Illuminate\Notifications\Messages\MailMessage;

class DeletedNotification extends UserMentionedNotification
{
    const ID = \Coyote\Notification::MICROBLOG_DELETE;

    public function __construct(Microblog $microblog, User $user)
    {
        $this->microblog = $microblog;
        $this->notifier = $user;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail()
    {
        return (new MailMessage())
            ->subject($this->getMailSubject())
            ->line(
                sprintf(
                    '<strong>%s</strong> usunął Twój wpis na mikroblogu: <strong>%s</strong>',
                    $this->notifier->name,
                    excerpt($this->microblog->html)
                )
            )
            ->line('Dostajesz to powiadomienie, ponieważ wynika to z ustawień Twojego konta.');
    }

    /**
     * @return string
     */
    protected function getMailSubject(): string
    {
        return 'Wpis został usunięty przez ' . $this->notifier->name;
    }
}
