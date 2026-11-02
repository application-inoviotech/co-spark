<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;

/**
 * Mirrors an office alert to the admin panel live over Pusher. Best-effort like
 * RealtimeChannel: the email still goes out and Pusher errors are only reported.
 */
class AdminRealtimeChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! RealtimeChannel::enabled() || ! method_exists($notification, 'toAdminAlert')) {
            return;
        }

        try {
            event($notification->toAdminAlert());
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
