<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An approved (or onboarding) COSPARK producer.
 * SOW: 75% commission; 2,000-token bonus credited once hired, licensed and holding the
 * $8.88 membership; redeemable after 12 months of writing insurance with COSPARK.
 */
class Producer extends Model
{
    public const QUALIFICATION_MONTHS = 12;

    public const BONUS_TOKENS = 2000;

    /** SOW: 10% off stays for the producer, family and friends (wherever the producer books). */
    public const VACATION_DISCOUNT = 0.10;

    protected $fillable = [
        'user_id', 'producer_application_id', 'producer_number', 'npn', 'business_name', 'business_address',
        'status', 'onboarded_on', 'qualification_started_on', 'commission_rate', 'payout_label',
        'bonus_tokens', 'bonus_status', 'bonus_credited_on', 'bonus_redemption_requested_at', 'auto_allowance_status',
    ];

    /** Program defaults (also the column defaults), available before the record is reloaded. */
    protected $attributes = [
        'status' => 'under_review',
        'commission_rate' => 0.75,
        'bonus_tokens' => self::BONUS_TOKENS,
        'bonus_status' => 'not_credited',
        'auto_allowance_status' => 'in_progress',
    ];

    protected $casts = [
        'onboarded_on' => 'date',
        'qualification_started_on' => 'date',
        'bonus_credited_on' => 'date',
        'bonus_redemption_requested_at' => 'datetime',
        'commission_rate' => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ProducerApplication::class, 'producer_application_id');
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(ProducerLicense::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProducerDocument::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Whole months of writing with COSPARK, capped for display by the caller. */
    public function monthsQualified(): int
    {
        return $this->qualification_started_on && $this->qualification_started_on->lte(today()) ? (int) $this->qualification_started_on->diffInMonths(today()) : 0;
    }

    public function eligibleOn(): ?Carbon
    {
        return $this->qualification_started_on?->copy()->addMonths(self::QUALIFICATION_MONTHS);
    }

    public function hasActiveLicense(): bool
    {
        return $this->licenses->contains(fn (ProducerLicense $l) => $l->isActive());
    }

    public function hasProducerMembership(): bool
    {
        $membership = $this->user->membership;

        return $membership && $membership->isPaid() && $membership->status === 'active';
    }

    /** @return array{approved: bool, licensed: bool, membership: bool, months: bool} */
    public function bonusEligibility(): array
    {
        return [
            'approved' => $this->isActive(),
            'licensed' => $this->hasActiveLicense(),
            'membership' => $this->hasProducerMembership(),
            'months' => $this->monthsQualified() >= self::QUALIFICATION_MONTHS,
        ];
    }
}
