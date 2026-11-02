<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Commission on a policy written by a producer (SOW: Commission History). */
class Commission extends Model
{
    protected $fillable = [
        'producer_id', 'policy_reference', 'insured_name', 'line_of_business', 'written_on',
        'premium', 'commission_base', 'rate', 'amount', 'status', 'paid_on',
    ];

    protected $casts = [
        'written_on' => 'date',
        'paid_on' => 'date',
        'premium' => 'decimal:2',
        'commission_base' => 'decimal:2',
        'rate' => 'decimal:4',
        'amount' => 'decimal:2',
    ];

    public function producer(): BelongsTo
    {
        return $this->belongsTo(Producer::class);
    }
}
