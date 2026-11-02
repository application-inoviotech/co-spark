<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirmation sent to the person who submitted a public form
 * (quote, claim, producer application, appointment, contact message).
 */
class SubmissionReceived extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param  string[]  $lines */
    public function __construct(
        public string $subject,
        public string $name,
        public string $reference,
        public array $lines = [],
        public bool $sms = true,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->sms ? ['mail', SmsChannel::class] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject.' — '.$this->reference)
            ->greeting("Hi {$this->name},");

        foreach ($this->lines as $line) {
            $mail->line($line);
        }

        return $mail->line("Your reference number is **{$this->reference}**. Keep it for your records.")
            ->salutation('— COSPARK Insurance Group');
    }

    public function toSms(object $notifiable): string
    {
        return "COSPARK: {$this->subject}. Ref {$this->reference}. Reply STOP to opt out.";
    }
}
