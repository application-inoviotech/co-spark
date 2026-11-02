<?php

namespace App\Models;

use App\Models\Concerns\HasReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** SOW: Vacations Planning — 90-day notice, subject to availability, tokens deducted on confirmation. */
class VacationRequest extends Model
{
    use HasReference;

    public const MIN_NOTICE_DAYS = 90;

    protected static string $referencePrefix = 'VAC';

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    protected $fillable = [
        'reference', 'user_id', 'partner_id', 'check_in', 'check_out', 'guests', 'traveller', 'notes', 'discount_rate',
        'tokens_applied', 'payment_amount', 'payment_status', 'status', 'instructions',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'payment_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function nights(): int
    {
        return (int) $this->check_in->diffInDays($this->check_out);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true);
    }
}
