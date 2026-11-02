<?php

namespace App\Services\Sms;

use App\Services\Klaviyo;

/** Text messages through Klaviyo: a "COSPARK SMS" event whose flow sends {{ event.message }}. */
class KlaviyoSmsSender implements SmsSender
{
    public function __construct(private Klaviyo $klaviyo) {}

    public function send(string $to, string $message): void
    {
        $this->klaviyo->eventOrReport(Klaviyo::SMS_METRIC, null, $to, ['message' => $message]);
    }
}
