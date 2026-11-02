<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasReference;
use Illuminate\Database\Eloquent\Model;

class ProducerApplication extends Model
{
    use HasAttachments, HasReference;

    /** Admin workflow (SOW: Submitted, Under Review, License Verification, Additional Information Required, Approved / Rejected). */
    public const STATUSES = ['submitted', 'under_review', 'license_verification', 'info_required', 'approved', 'rejected'];

    protected static string $referencePrefix = 'PRD';

    protected $fillable = [
        'reference', 'first_name', 'last_name', 'email', 'phone', 'street', 'city', 'state', 'zip',
        'occupation', 'experience', 'years_experience', 'previous_company', 'lines_of_business',
        'license_status', 'license_number', 'license_type', 'license_state', 'license_issued_on', 'license_expires_on',
        'reason', 'comments', 'status', 'admin_notes', 'terms_accepted_at',
    ];

    protected $casts = [
        'lines_of_business' => 'array',
        'license_issued_on' => 'date',
        'license_expires_on' => 'date',
        'terms_accepted_at' => 'datetime',
    ];
}
