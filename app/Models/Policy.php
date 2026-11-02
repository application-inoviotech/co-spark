<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** An insurance policy held by a member (SOW: My Policies — multiple policies per membership). */
class Policy extends Model
{
    /** COSPARK policy numbers: CSP-{type code}-{6 digits}, e.g. CSP-REN-000123 (the claim form shows this format). */
    public const NUMBER_PATTERN = '/^CSP-[A-Z]{3}-\d{6}$/';

    private const TYPE_CODES = ['Auto' => 'AUT', 'Home' => 'HOM', 'Renters' => 'REN', 'Condo' => 'CON', 'Life' => 'LIF', 'Health' => 'HLT', 'Commercial' => 'COM', 'Umbrella' => 'UMB', 'Other' => 'OTH'];

    public static function numberPrefix(string $type): string
    {
        return 'CSP-'.(self::TYPE_CODES[$type] ?? 'OTH').'-';
    }

    /** The next free number for a type: CSP-REN-000001, CSP-REN-000002, … */
    public static function nextNumber(string $type): string
    {
        $prefix = self::numberPrefix($type);
        $last = self::where('policy_number', 'like', $prefix.'%')->pluck('policy_number')
            ->map(fn ($n) => preg_match(self::NUMBER_PATTERN, $n) ? (int) substr($n, -6) : 0)->max() ?? 0;

        return $prefix.str_pad((string) ($last + 1), 6, '0', STR_PAD_LEFT);
    }

    protected $fillable = [
        'user_id', 'type', 'policy_number', 'carrier', 'insured_name', 'covers', 'starts_on', 'expires_on',
        'annual_premium', 'amount_due', 'due_on', 'status', 'renewal_note', 'cancelled_at',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'expires_on' => 'date',
        'due_on' => 'date',
        'annual_premium' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    /** Used by the admin policy form. */
    public function getMemberEmailAttribute(): ?string
    {
        return $this->user?->email;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PolicyDocument::class);
    }

    /** The File a Claim "insurance type" this policy is claimed under (Business → Commercial, unlisted lines → Other). */
    public function claimType(): string
    {
        return in_array($this->type, \App\Http\Requests\StoreClaimRequest::INSURANCE_TYPES, true)
            ? $this->type
            : ($this->type === 'Business' ? 'Commercial' : 'Other');
    }

    /** Policy year label used for year-based Insurance Token tracking, e.g. "2026–27". */
    public function policyYear(): string
    {
        return $this->starts_on->format('Y').'–'.$this->expires_on->format('y');
    }

    public function icon(): string
    {
        return match ($this->type) {
            'Home', 'Condo', 'Renters' => 'home',
            'Auto' => 'car',
            'Health', 'Life' => 'heart',
            'Commercial', 'Business' => 'briefcase',
            default => 'shield',
        };
    }
}
