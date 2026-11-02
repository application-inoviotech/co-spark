<?php

namespace App\Notifications;

use App\Notifications\Channels\RealtimeChannel;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * An entry in the member or producer notification centre (SOW: Notifications), optionally
 * also sent by email and text. `section` is the portal section it links to; `portal` is
 * "member" (member dashboard) or "producer" (producer portal).
 */
class MemberAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $category,
        public string $title,
        public string $summary,
        public string $section,
        public bool $mail = false,
        public string $portal = 'member',
    ) {}

    public function via(object $notifiable): array
    {
        return $this->mail ? ['database', RealtimeChannel::class, 'mail', SmsChannel::class] : ['database', RealtimeChannel::class];
    }

    /** The dashboard entry and live alert are instant; email and SMS go through the queue. */
    public function viaConnections(): array
    {
        return ['database' => 'sync', RealtimeChannel::class => 'sync'];
    }

    /** Same shape as MemberDashboardPresenter::notifications(), so the dashboard can add it as-is. */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        // Pushed at once rather than waiting for the queue worker
        return (new BroadcastMessage([...$this->toArray($notifiable), 'date' => today()->toDateString(), 'read' => false]))->onConnection('sync');
    }

    public function toArray(object $notifiable): array
    {
        return ['cat' => $this->category, 't' => $this->title, 's' => $this->summary, 'go' => $this->section, 'portal' => $this->portal];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route($this->portal === 'producer' ? 'producer.dashboard' : 'dashboard').'#'.$this->section;

        return (new MailMessage)
            ->subject("COSPARK: {$this->title}")
            ->greeting("Hi {$notifiable->first_name},")
            ->line($this->title)
            ->line($this->summary)
            ->action($this->portal === 'producer' ? 'Open the producer portal' : 'Open my dashboard', $url)
            ->salutation('— COSPARK Insurance Group');
    }

    public function toSms(object $notifiable): string
    {
        return "COSPARK: {$this->title}. {$this->summary}";
    }
}
