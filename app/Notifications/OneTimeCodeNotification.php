<?php

namespace App\Notifications;

use App\Models\OneTimeCode;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OneTimeCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
        public string $purpose,
        public int $ttlMinutes,
    ) {}

    /** Sent at once (not queued) over SMTP, like every email. */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetting = $this->purpose === OneTimeCode::RESET_PASSWORD;

        return (new MailMessage)
            ->subject($resetting ? 'Your COSPARK password reset code' : 'Verify your COSPARK email')
            ->line($resetting ? 'Use this code to reset your password:' : 'Use this code to verify your email address:')
            ->line("**{$this->code}**")
            ->line("The code expires in {$this->ttlMinutes} minutes. If you didn't request it, you can ignore this email.")
            ->salutation('— COSPARK Insurance Group');
    }
}
