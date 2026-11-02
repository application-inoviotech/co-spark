<?php

/*
|--------------------------------------------------------------------------
| Membership plans & token rules (SOW: Membership Selection, Token Categories)
|--------------------------------------------------------------------------
| Defaults only: plans and the sub-account plan are edited in admin (Membership plans)
| and applied over these values at boot by App\Models\MembershipPlan::applyToConfig().
*/

return [

    'plans' => [
        'free' => [
            'name' => 'Free',
            'tagline' => 'Digital access',
            'description' => 'A simple way to keep your insurance information available digitally.',
            'monthly_fee' => 0,
            'dues_tokens' => 0,          // DT per paid month
            'insurance_token_rate' => 0, // IT as a share of eligible annual premium
            'perks' => ['Digital member account', 'Policy management', 'No membership tokens'],
        ],
        'associate' => [
            'name' => 'Associate',
            'tagline' => 'Everyday value',
            'description' => 'Participate in the COSPARK token program with monthly allocations.',
            'monthly_fee' => 8.88,
            'dues_tokens' => 4,
            'insurance_token_rate' => 0.05,
            'perks' => ['4 Dues Tokens / month', '5% of eligible premium as IT', 'Full dashboard & QR benefits'],
        ],
        'premium' => [
            'name' => 'Premium',
            'tagline' => 'Expanded value',
            'description' => 'The fullest expression of COSPARK membership and token participation.',
            'monthly_fee' => 25.00,
            'dues_tokens' => 12,
            'insurance_token_rate' => 0.07,
            'perks' => ['12 Dues Tokens / month', '7% of eligible premium as IT', 'Sub-accounts at $8.88/mo each'],
        ],
    ],

    'sub_account_fee' => 8.88,

    // Linked to a Premium main member, who is billed $8.88/month per sub-account
    'sub_account_plan' => [
        'name' => 'Sub-account',
        'monthly_fee' => 8.88,
        'dues_tokens' => 0,
        'insurance_token_rate' => 0,
        'perks' => ['Partner benefits', 'QR code', 'Vacation browsing'],
    ],

    'sub_account_permissions' => ['Partner benefits', 'QR code', 'Vacations', 'View policies', 'Appointments'],

    // Vacation planning estimates used until a property sets its own rate
    'vacations' => [
        'default_nightly_rate' => 285,
        'default_tokens_per_night' => 45,
        'max_guests' => 8,
    ],

    'vacation_token_minimum' => 25,

    // label => months covered by one payment
    'frequencies' => [
        'monthly' => ['label' => 'Monthly', 'months' => 1],
        'quarterly' => ['label' => 'Quarterly', 'months' => 3],
        'biannual' => ['label' => 'Bi-annual', 'months' => 6],
        'annual' => ['label' => 'Annual', 'months' => 12],
    ],

    'otp' => [
        'length' => 6,
        'fixed_code' => env('OTP_FIXED_CODE'), // testing only — never set in production
        'ttl_minutes' => 10,
        'max_attempts' => 5,
    ],
];
