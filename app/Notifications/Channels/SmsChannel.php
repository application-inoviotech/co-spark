<?php

namespace App\Notifications\Channels;

use App\Services\Sms\SmsSender;
use Illuminate\Notifications\Notification;

/** Delivers a notification's toSms() text to the notifiable's phone number. */
class SmsChannel
{
    public function __construct(private SmsSender $sender) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $to = $notifiable->routeNotificationFor('sms', $notification);

        if ($to && config('cospark.sms_enabled') && method_exists($notification, 'toSms')) {
            $this->sender->send($to, $notification->toSms($notifiable));
        }
    }
}
