<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A quote request captured by the dynamic quote engine or the home page mini quote. */
class Lead extends Model
{
    use HasAttachments, HasReference;

    protected static string $referencePrefix = 'QT';

    protected $fillable = [
        'reference', 'source', 'insurance_type', 'zip', 'first_name', 'last_name', 'email', 'phone',
        'preferred_contact', 'street', 'city', 'state', 'notes', 'producer_notes', 'follow_up_on', 'details', 'status', 'user_id', 'producer_id', 'assigned_at',
    ];

    /** Follow-up pipeline used by the office and assigned producers (SOW: Leads Management). */
    public const STATUSES = ['new', 'contacted', 'quoted', 'won', 'lost'];

    protected $casts = ['details' => 'array', 'follow_up_on' => 'date', 'assigned_at' => 'datetime'];

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function producer(): BelongsTo
    {
        return $this->belongsTo(Producer::class);
    }

    /** Where the lead came from, as shown to producers. */
    public function sourceLabel(): string
    {
        return match ($this->source) {
            'quote' => 'Website quote',
            'home' => 'Home page quote',
            default => ucfirst($this->source),
        };
    }

    public function interestLabel(): string
    {
        return config("cospark.quote_types.{$this->insurance_type}")
            ?? ucwords(str_replace('-', ' ', $this->insurance_type));
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
