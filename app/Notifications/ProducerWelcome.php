<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when an admin approves and onboards a producer: the sign-in details for the producer portal.
 * A new account gets a temporary password; an existing member keeps theirs ($password is null).
 */
class ProducerWelcome extends Notification
{
    use Queueable;

    public function __construct(public ?string $password) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Welcome to COSPARK — your producer account')
            ->greeting("Hi {$notifiable->first_name},")
            ->line('Your producer application has been approved. You can now sign in to the COSPARK producer portal.')
            ->line("**Email:** {$notifiable->email}");

        $this->password
            ? $mail->line("**Temporary password:** {$this->password}")
                ->line('For your security, change it after signing in using "Forgot password" on the sign-in page.')
            : $mail->line('**Password:** use your existing COSPARK password.');

        return $mail
            ->action('Sign in', route('login'))
            ->line('Then activate your $8.88 membership from the portal to qualify for the producer token bonus.')
            ->salutation('— COSPARK Insurance Group');
    }
}
