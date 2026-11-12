<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Emails a registration link. Sent on-demand to the invite address (there's no
 * User yet). Synchronous on purpose: prod runs no queue worker, and the admin
 * should know right away if delivery failed.
 */
class InviteNotification extends Notification
{
    public function __construct(
        private string $url,
        private ?Carbon $expiresAt,
        private ?string $invitedBy = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You're invited to Dyno")
            ->greeting("You're invited to Dyno")
            ->line(($this->invitedBy ? "{$this->invitedBy} invited you" : "You've been invited")
                .' to Dyno — curated strength training for climbers.')
            ->action('Create your account', $this->url)
            ->line('This invite works once, only for this email address'
                .($this->expiresAt ? ', and expires '.$this->expiresAt->format('M j') : '').'.')
            ->salutation('— Dyno');
    }
}
