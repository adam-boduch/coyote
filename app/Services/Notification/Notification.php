<?php

namespace Coyote\Services\Notification;

use Coyote\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification as BaseNotification;

abstract class Notification extends BaseNotification
{
    protected $broadcast = [];

    /**
     * @param \Coyote\User $user
     * @return array
     */
    abstract public function toDatabase($user);

    /**
     * @return array
     */
    abstract public function sender();

    /**
     * Generowanie unikalnego ciagu znakow dla wpisu na mikro
     *
     * @return string
     */
    abstract public function objectId();

    /**
     * Get the notification's delivery channels.
     *
     * @param  User  $user
     * @return array
     */
    public function via($user)
    {
        return $this->getChannels($user);
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return $this->broadcast;
    }

    /**
     * @param User $user
     * @return array
     */
    protected function getChannels(User $user)
    {
        $channels = [];
        $settings = $user->notificationSettings()->where('type_id', static::ID)->first();

        if ($settings->profile) {
            $channels[] = DatabaseChannel::class;
        }

        if ($user->email && $user->is_active && $user->is_confirm && !$user->is_blocked && $settings->email) {
            $channels[] = 'mail';
        }

        if ($this instanceof ShouldBroadcast) {
            $channels[] = 'broadcast';
            $this->broadcast[] = $user->receivesBroadcastNotificationsOn();
        }

        return $channels;
    }

    /**
     * @return string
     */
    protected function notificationUrl()
    {
        return route('user.notifications.url', [$this->id]);
    }
}
