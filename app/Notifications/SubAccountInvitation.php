<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubAccountInvitation extends Notification
{
    use Queueable;

    public function __construct(public string $ownerName) {}

    /** Sent at once (not queued), together with the code it explains: the sub-account can't sign in without it. */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->ownerName} added you to their COSPARK membership")
            ->greeting("Hi {$notifiable->first_name},")
            ->line("{$this->ownerName} has added you as a sub-account on their COSPARK Premium membership.")
            ->line('We just emailed you a 6-digit code. Use it on the reset password page with this email address to set your password.')
            ->line('If the code has expired, enter your email on that page to get a new one.')
            ->action('Set my password', route('password.request'))
            ->salutation('— COSPARK Insurance Group');
    }
}
