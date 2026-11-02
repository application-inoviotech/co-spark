<?php

namespace App\Services;

use App\Notifications\NewSubmission;
use App\Notifications\SubmissionReceived;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Sends the customer confirmation (email + SMS) and the office alert for a submission.
 * Delivery problems are logged and never fail the request: the record is already saved.
 */
class SubmissionNotifier
{
    /** @param  array<string, string|null>  $summary */
    public function notify(
        SubmissionReceived $confirmation,
        string $email,
        ?string $phone,
        string $kind,
        array $summary,
    ): void {
        try {
            Notification::route('mail', $email)
                ->route('sms', $phone)
                ->notify($confirmation);

            Notification::route('mail', config('cospark.admin_notify_email'))
                ->notify(new NewSubmission($kind, $confirmation->reference, $summary));
        } catch (Throwable $e) {
            Log::error("Failed to send {$kind} notifications for {$confirmation->reference}", ['exception' => $e]);
        }
    }
}
