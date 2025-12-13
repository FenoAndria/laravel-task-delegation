<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * An in-app notification, optionally mirrored by email.
 */
abstract class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Whether the notification also deserves an email.
     */
    protected bool $mail = false;

    /**
     * @return array{organization_id: int, title: string, body: string|null, url: string}
     */
    abstract protected function message(): array;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->mail ? ['database', 'broadcast', 'mail'] : ['database', 'broadcast'];
    }

    /**
     * @return array{organization_id: int, title: string, body: string|null, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return $this->message();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = $this->message();

        return (new MailMessage)
            ->subject($message['title'])
            ->line($message['title'])
            ->when($message['body'], fn (MailMessage $mail) => $mail->line((string) $message['body']))
            ->action('Open', $message['url']);
    }
}
