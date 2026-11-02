<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Channels\BroadcastChannel;
use Illuminate\Notifications\Notification;

/**
 * Pushes a notification to the user's open dashboards over Pusher (private-App.Models.User.{id}).
 * Live delivery is best-effort: the notification is already saved and emailed, so a Pusher
 * outage or bad keys are reported and never break the request that raised the notification.
 */
class RealtimeChannel extends BroadcastChannel
{
    public static function enabled(): bool
    {
        return config('broadcasting.default') === 'pusher' && filled(config('broadcasting.connections.pusher.key'));
    }

    public function send($notifiable, Notification $notification)
    {
        if (! self::enabled()) {
            return null;
        }

        try {
            return parent::send($notifiable, $notification);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
