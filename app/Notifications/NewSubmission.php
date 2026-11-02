<?php

namespace App\Notifications;

use App\Events\AdminAlert;
use App\Notifications\Channels\AdminRealtimeChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Alert to the office inbox when a public form is submitted. */
class NewSubmission extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param  array<string, string|null>  $summary */
    public function __construct(
        public string $kind,
        public string $reference,
        public array $summary,
    ) {}

    public function via(object $notifiable): array
    {
        return [AdminRealtimeChannel::class, 'mail']; // live alert first, so it arrives even if SMTP fails
    }

    /** The admin panel alert is instant; the office email goes through the queue. */
    public function viaConnections(): array
    {
        return [AdminRealtimeChannel::class => 'sync'];
    }

    /** Live copy for the admin panel, linking to the admin section that holds the record. */
    public function toAdminAlert(): AdminAlert
    {
        $section = collect([
            'appointment' => 'appointment', 'claim' => 'claim', 'contact' => 'contact-message', 'quote' => 'lead',
            'application' => 'producer-application', 'vacation' => 'vacation-request', 'producer' => 'producer',
        ])->first(fn ($key, $word) => str_contains($this->kind, $word));

        return new AdminAlert('New '.$this->kind, $this->reference, $section ? route('admin.resources.index', $section, false) : route('admin.dashboard', [], false));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("New {$this->kind} — {$this->reference}")
            ->greeting("New {$this->kind}")
            ->line("Reference: **{$this->reference}**");

        foreach (array_filter($this->summary) as $label => $value) {
            $mail->line("{$label}: {$value}");
        }

        return $mail->line('Review it in the COSPARK admin dashboard.');
    }
}
