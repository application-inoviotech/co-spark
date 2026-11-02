<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Claim extends Model
{
    use HasAttachments, HasReference;

    /** Progress timeline shown to the member (SOW: Claim Progress / Timeline). */
    public const STEPS = ['Submitted', 'In review', 'Adjuster assigned', 'Decision', 'Closed'];

    protected static string $referencePrefix = 'CLM';

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    protected $fillable = [
        'reference', 'user_id', 'policy_id', 'first_name', 'last_name', 'email', 'phone', 'preferred_contact',
        'policy_number', 'insurance_type', 'claim_type', 'incident_date', 'incident_time',
        'location', 'description', 'details', 'comments', 'status', 'progress_step', 'required_action', 'outstanding_documents',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'details' => 'array',
        'outstanding_documents' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ClaimMessage::class)->latest();
    }
}
