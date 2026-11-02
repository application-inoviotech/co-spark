<?php

namespace App\Models;

use App\Models\Concerns\HasReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    use HasReference;

    public const TYPE_DUES = 'membership_dues';

    public const TYPE_PREMIUM = 'insurance_premium';

    public const TYPE_VACATION_TOKENS = 'vacation_tokens';

    public const TYPE_SUB_ACCOUNT = 'sub_account';

    protected static string $referencePrefix = 'PAY';

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    protected $fillable = [
        'reference', 'user_id', 'payable_type', 'payable_id', 'type', 'description', 'amount', 'status', 'method_label', 'payment_method_id',
        'gateway', 'gateway_environment', 'gateway_reference', 'failure_reason', 'period_start', 'period_end', 'paid_at',
        'membership_plan', 'billing_frequency', 'refund_reconciled_at', 'refund_review_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'paid_at' => 'datetime',
        'refund_reconciled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if ($payment->gateway === 'authorizenet') {
                $payment->gateway_environment ??= config('services.authorizenet.sandbox') ? 'sandbox' : 'production';
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function succeeded(): bool
    {
        return $this->status === 'succeeded';
    }
}
