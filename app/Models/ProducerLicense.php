<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProducerLicense extends Model
{
    /** Licenses expiring within this many days are flagged for renewal. */
    public const RENEWAL_WARNING_DAYS = 90;

    protected $fillable = ['producer_id', 'license_type', 'license_number', 'state', 'issued_on', 'expires_on', 'document_path', 'verified_at'];

    protected $casts = [
        'issued_on' => 'date',
        'expires_on' => 'date',
        'verified_at' => 'datetime',
    ];

    public function producer(): BelongsTo
    {
        return $this->belongsTo(Producer::class);
    }

    public function isActive(): bool
    {
        return $this->verified_at !== null && $this->expires_on->gte(today());
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->expires_on->lt(today()) => 'Expired',
            $this->verified_at === null => 'Pending verification',
            $this->expires_on->lte(today()->addDays(self::RENEWAL_WARNING_DAYS)) => 'Expiring soon',
            default => 'Active',
        };
    }
}
