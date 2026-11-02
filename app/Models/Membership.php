<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

class Membership extends Model
{
    protected $fillable = ['user_id', 'plan', 'pending_plan', 'billing_frequency', 'monthly_fee', 'status', 'auto_renew', 'started_at', 'current_period_end', 'cancelled_at', 'billing_status', 'expired_at'];

    protected $casts = [
        'monthly_fee' => 'decimal:2',
        'auto_renew' => 'boolean',
        'started_at' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /** Plan settings from config/membership.php. */
    public function planConfig(): array
    {
        if ($this->plan === 'sub_account') {
            return config('membership.sub_account_plan');
        }

        return config("membership.plans.{$this->plan}", config('membership.plans.free'));
    }

    public function isPaid(): bool
    {
        return $this->monthly_fee > 0;
    }

    /** SOW: 60-month redemption cycle, counted from the start date (as on the member dashboard). */
    public const REDEMPTION_MONTHS = 60;

    public function journeyStart(): ?Carbon
    {
        return $this->started_at ?? $this->user?->created_at;
    }

    /** Current month of the 60-month journey (1–60). */
    public function journeyMonth(): ?int
    {
        return $this->journeyStart() ? min(self::REDEMPTION_MONTHS, (int) $this->journeyStart()->diffInMonths(now()) + 1) : null;
    }

    public function redemptionEligibleOn(): ?Carbon
    {
        return $this->journeyStart()?->copy()->addMonths(self::REDEMPTION_MONTHS);
    }

    public function isRedemptionEligible(): bool
    {
        return (bool) $this->redemptionEligibleOn()?->isPast();
    }

    /** "Month 24 / 60" or "Eligible" for admin tables. */
    public function getJourneyLabelAttribute(): ?string
    {
        if (! $this->journeyStart()) {
            return null;
        }

        return $this->isRedemptionEligible() ? 'Eligible' : 'Month '.$this->journeyMonth().' / '.self::REDEMPTION_MONTHS;
    }

    public function frequencyMonths(): int
    {
        return config("membership.frequencies.{$this->billing_frequency}.months", 1);
    }

    /** Amount charged per billing period. */
    public function periodAmount(): float
    {
        return round($this->monthly_fee * $this->frequencyMonths(), 2);
    }

    /** Dues Tokens credited for one paid billing period. */
    public function periodDuesTokens(): int
    {
        return $this->planConfig()['dues_tokens'] * $this->frequencyMonths();
    }
}
