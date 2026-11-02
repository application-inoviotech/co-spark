<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One Vacation Token purchase (SOW: 1 VT = $1 face value, accrues 0.75% monthly).
 * Purchase date, face value, accrued value and usage are tracked per lot.
 */
class VacationTokenLot extends Model
{
    public const MONTHLY_ACCRUAL = 0.0075;

    protected $fillable = ['user_id', 'payment_id', 'lot_number', 'purchased_on', 'face_value', 'used_value', 'status'];

    protected $casts = [
        'purchased_on' => 'date',
        'face_value' => 'decimal:2',
        'used_value' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Value accrued on the face amount since purchase (illustrative until program terms are final). */
    public function accruedValue(): float
    {
        if ($this->status !== 'active') {
            return 0;
        }
        $months = (int) $this->purchased_on->diffInMonths(now());

        return round($this->face_value * ((1 + self::MONTHLY_ACCRUAL) ** $months - 1), 2);
    }
}
