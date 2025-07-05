<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as Notifier;

class Notify
{
    /**
     * Send to every given person except the one who caused it.
     *
     * @param  iterable<User|null>  $recipients
     */
    public static function send(iterable $recipients, Notification $notification, ?User $actor = null): void
    {
        $users = collect($recipients)
            ->filter()
            ->unique('id')
            ->reject(fn (User $user) => $actor !== null && $user->is($actor));

        if ($users->isNotEmpty()) {
            Notifier::send($users, $notification);
        }
    }
}
