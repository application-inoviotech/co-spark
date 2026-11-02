<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Plain email update from the office (applications, appointments, claims) to any recipient. */
class StatusUpdate extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * [button text, url]. A plain property with a default (not constructor-promoted) so jobs queued
     * before it existed still unserialize with null instead of an uninitialized property.
     *
     * @var array{0: string, 1: string}|null
     */
    public ?array $action = null;

    /**
     * @param  string[]  $lines
     * @param  array{0: string, 1: string}|null  $action
     */
    public function __construct(public string $subject, public string $name, public array $lines, ?array $action = null)
    {
        $this->action = $action;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->subject)->greeting("Hi {$this->name},");
        foreach ($this->lines as $line) {
            $mail->line($line);
        }
        if ($this->action) {
            $mail->action(...$this->action);
        }

        return $mail->salutation('— COSPARK Insurance Group');
    }
}
