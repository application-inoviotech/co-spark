<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\Billing\RefundReconciler;
use Illuminate\Console\Command;

class ReconcileRefundRewards extends Command
{
    protected $signature = 'billing:reconcile-refunds {--payment=* : Specific already-refunded payment references} {--all : Reconcile all already-refunded payments}';

    protected $description = 'Repair historical refunded rewards/entitlements without sending any gateway charge or refund';

    public function handle(RefundReconciler $refunds): int
    {
        $refs = $this->option('payment');
        if (! $refs && ! $this->option('all')) {
            $this->error('Specify --payment=PAY-REFERENCE or --all. No records changed.');

            return self::FAILURE;
        }
        $query = Payment::where('status', 'refunded')->orderBy('id');
        if ($refs) {
            $query->whereIn('reference', $refs);
        }
        $review = false;
        foreach ($query->cursor() as $payment) {
            $result = $refunds->confirmed($payment);
            $this->line($payment->reference.': '.($result['review'] ?: 'reconciled'));
            $review = $review || (bool) $result['review'];
        }

        return $review ? self::FAILURE : self::SUCCESS;
    }
}
