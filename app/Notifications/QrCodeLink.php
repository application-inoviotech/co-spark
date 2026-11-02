<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SOW: QR Code — "send to my phone". A link to the member's QR page by text (when they have
 * opted in to texts) and email. Not added to the notification centre: the member asked for it.
 */
class QrCodeLink extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', SmsChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your COSPARK member QR code')
            ->greeting("Hi {$notifiable->first_name},")
            ->line('Show your member QR code at participating partners to use your benefits.')
            ->action('Open my QR code', $this->url())
            ->salutation('— COSPARK Insurance Group');
    }

    public function toSms(object $notifiable): string
    {
        return 'COSPARK: Your member QR code — show it at participating partners: '.$this->url();
    }

    private function url(): string
    {
        return route('dashboard').'#qr';
    }
}
