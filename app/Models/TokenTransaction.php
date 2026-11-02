<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One entry in a member's token ledger. Balances are the sum of entries per token type. */
class TokenTransaction extends Model
{
    public const DUES = 'DT';

    public const INSURANCE = 'IT';

    public const VACATION = 'VT';

    protected $fillable = ['user_id', 'token_type', 'kind', 'amount', 'description', 'reference', 'status', 'payment_id', 'policy_id', 'policy_year'];

    protected $casts = ['amount' => 'decimal:2'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
