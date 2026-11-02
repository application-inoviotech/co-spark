<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Notifications\MemberAlert;
use Illuminate\Database\Eloquent\Model;

/**
 * Records and charges a payment (SOW: Payment Confirmation — record the transaction,
 * confirm on screen, notify by email/SMS; failed payments are marked and notified).
 */
class PaymentProcessor
{
    public function __construct(private PaymentGateway $gateway) {}

    /**
     * @param  array{period_start?: mixed, period_end?: mixed}  $period
     */
    public function charge(
        User $user,
        string $type,
        float $amount,
        string $description,
        CardDetails $card,
        ?PaymentMethod $method = null,
        ?Model $payable = null,
        array $period = [],
    ): Payment {
        $payment = $user->payments()->create([
            'payable_type' => $payable?->getMorphClass(),
            'payable_id' => $payable?->getKey(),
            'type' => $type,
            'description' => $description,
            'amount' => round($amount, 2),
            'status' => 'pending',
            'method_label' => $card->label(),
            'payment_method_id' => $method?->id,
            'gateway' => $this->gateway->name(),
            'period_start' => $period['period_start'] ?? null,
            'period_end' => $period['period_end'] ?? null,
        ]);

        $result = $method?->isExpired()
            ? ChargeResult::failure("Your card ending {$method->last4} has expired.")
            : $this->gateway->charge($user, $payment->amount, $description, $card);

        if ($result->successful) {
            $payment->update(['status' => 'succeeded', 'gateway_reference' => $result->reference, 'paid_at' => now()]);
            $alert = new MemberAlert('Payments', 'Payment received · $'.number_format($payment->amount, 2), $description, 'payments', mail: true);
        } else {
            $payment->update(['status' => 'failed', 'failure_reason' => $result->failureReason]);
            $alert = new MemberAlert('Payments', 'Payment failed', "{$description} — {$result->failureReason}", 'payments', mail: true);
        }

        // The payment is already recorded; a mail or SMS outage must not turn it into an error
        try {
            $user->notify($alert);
        } catch (\Throwable $e) {
            report($e);
        }

        return $payment;
    }
}
