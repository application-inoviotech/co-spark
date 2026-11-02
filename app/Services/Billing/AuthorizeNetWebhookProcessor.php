<?php

namespace App\Services\Billing;

use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthorizeNetWebhookProcessor
{
    private const EVENTS = [
        'net.authorize.payment.authcapture.created',
        'net.authorize.payment.capture.created',
        'net.authorize.payment.priorAuthCapture.created',
        'net.authorize.payment.refund.created',
        'net.authorize.payment.void.created',
    ];

    public function __construct(private AuthorizeNet $gateway, private RefundReconciler $refunds) {}

    public function process(int $id): void
    {
        DB::transaction(function () use ($id) {
            $event = PaymentWebhookEvent::whereKey($id)->lockForUpdate()->firstOrFail();
            $environment = config('services.authorizenet.sandbox') ? 'sandbox' : 'production';
            if ($event->environment !== $environment || $event->status !== 'pending' || $event->next_attempt_at?->isFuture()) {
                return;
            }
            if (! in_array($event->event_type, self::EVENTS, true) || ! $event->transaction_id) {
                $event->update(['status' => 'ignored', 'processed_at' => now()]);

                return;
            }
            $event->attempts++;

            try {
                $transaction = $this->gateway->transactionDetails($event->transaction_id);
                if ((string) ($transaction['transId'] ?? '') !== $event->transaction_id) {
                    $this->review($event, 'Gateway transaction identifier mismatch.');

                    return;
                }
                $status = $transaction['transactionStatus'] ?? '';
                $isRefund = ($transaction['transactionType'] ?? '') === 'refundTransaction';
                // A refund notification identifies the refund transaction, not the original charge.
                $originalId = $isRefund ? ($transaction['refTransId'] ?? null) : $event->transaction_id;
                if (! is_scalar($originalId) || ! preg_match('/\A[0-9]{1,32}\z/', (string) $originalId)) {
                    $this->review($event, 'Original charge identifier unavailable.');

                    return;
                }
                $matches = Payment::where('gateway', 'authorizenet')->where('gateway_reference', (string) $originalId)->get();
                if ($matches->count() !== 1) {
                    // A notification may arrive before the synchronous charge commits. Retry the inbox.
                    $this->retry($event, 'Charge not uniquely matched; synchronous recording may still be in progress.');

                    return;
                }
                $payment = $matches->first();
                if ($payment->gateway_environment !== $event->environment) {
                    $this->review($event, 'Payment environment unknown or different; verify historical provenance manually.');

                    return;
                }
                $amount = $isRefund ? ($transaction['settleAmount'] ?? $transaction['authAmount'] ?? null)
                    : ($transaction['authAmount'] ?? null);
                if (! is_numeric($amount) || ! is_finite((float) $amount) || (float) $amount <= 0 ||
                    number_format((float) $amount, 2, '.', '') !== number_format((float) $payment->amount, 2, '.', '')) {
                    $this->review($event, 'Amount mismatch or partial refund; manual reconciliation required.');

                    return;
                }

                if ($status === 'voided' || ($isRefund && in_array($status, ['refundPendingSettlement', 'refundSettledSuccessfully'], true))) {
                    $result = $this->refunds->confirmed($payment);
                    if ($result['review']) {
                        $this->review($event, $result['review']);
                    } else {
                        $event->update(['status' => 'processed', 'last_error' => null, 'processed_at' => now()]);
                    }

                    return;
                }
                if (! $isRefund && in_array($status, ['capturedPendingSettlement', 'settledSuccessfully'], true)) {
                    if ($payment->status === 'refunded') {
                        // Late capture events cannot resurrect a refunded payment.
                        $event->update(['status' => 'processed', 'last_error' => null, 'processed_at' => now()]);

                        return;
                    }
                    if ($payment->status !== 'succeeded') {
                        $this->review($event, 'Captured charge needs business fulfillment review; no tokens or membership granted automatically.');

                        return;
                    }
                    $event->update(['status' => 'processed', 'last_error' => null, 'processed_at' => now()]);

                    return;
                }
                $this->review($event, 'Gateway state needs manual review; payment status left unchanged.');
            } catch (\Throwable $e) {
                // Do not persist/log API response bodies, credentials or customer details from exceptions.
                $this->retry($event, 'Gateway lookup or reconciliation failed; retry required.');
            }
        });
    }

    private function retry(PaymentWebhookEvent $event, string $reason): void
    {
        if ($event->attempts >= 10) {
            $this->review($event, $reason);

            return;
        }
        $event->fill(['last_error' => $reason, 'next_attempt_at' => now()->addMinutes(min(60, 2 ** $event->attempts))])->save();
    }

    private function review(PaymentWebhookEvent $event, string $reason): void
    {
        $event->fill(['status' => 'needs_review', 'last_error' => $reason, 'processed_at' => now(), 'next_attempt_at' => null])->save();
        Log::warning('Authorize.net webhook requires review.', ['notification_id' => $event->notification_id, 'reason' => $reason]);
    }
}
