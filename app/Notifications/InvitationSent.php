<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationSent extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invitation $invitation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $organization = $this->invitation->organization->name;
        $inviter = $this->invitation->inviter->name ?? 'Someone';

        return (new MailMessage)
            ->subject("You are invited to join {$organization}")
            ->greeting('Hello!')
            ->line("{$inviter} invited you to join **{$organization}** as {$this->invitation->role->label()}.")
            ->action('View invitation', route('invitations.show', $this->invitation->token))
            ->line('This invitation expires on '.$this->invitation->expires_at->toFormattedDayDateString().'.')
            ->line('If you were not expecting it, you can ignore this email.');
    }
}
