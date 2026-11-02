<?php

/*
|--------------------------------------------------------------------------
| COSPARK site configuration
|--------------------------------------------------------------------------
| Office details and navigation shared by the public layout. Navigation
| entries mirror the approved prototype menus (SOW: Main Navigation).
| Each link is [label, route name, route params, url fragment].
*/

return [

    'office' => [
        'name' => 'COSPARK Insurance Group',
        'dba' => 'DBA Skrelja Farmers Insurance Group',
        'phone' => '(248) 555-0188',
        'phone_tel' => '2485550188',
        'roadside_phone' => '1-800-555-0199',
        'roadside_tel' => '18005550199',
        'email' => 'info@cosparkinsurance.com',
        'address_line1' => '414 S Main Street, Suite 201',
        'address_line2' => 'Rochester, MI 48307',
        'hours' => 'Monday–Thursday · 9:00 AM–4:00 PM',
        'token_transfer_email' => 'keyscove8@yahoo.com',
    ],

    'admin_notify_email' => env('ADMIN_NOTIFY_EMAIL', 'info@cosparkinsurance.com'),

    // Notification & billing settings (editable in Admin → Settings)
    'sms_enabled' => true,
    'billing_grace_days' => 3, // daily retries of a declined renewal before the membership expires

    // Legal page texts (placeholders until COSPARK supplies final language; editable in Admin → Settings)
    'legal' => [
        'privacy' => 'How COSPARK collects, uses, stores and protects personal information, including quote, claim, policy, payment and membership data. Covers data sharing with carriers and service providers, cookies, retention and your rights.',
        'terms' => 'Terms governing use of the website, member and producer accounts, membership billing, token programs (DT, IT, VT), transfers, redemption, vacation requests and partner benefits.',
        'disclaimers' => 'Insurance coverage is subject to underwriting, availability and policy terms. Membership and token programs are separate from insurance. Member-to-member token arrangements include hold-harmless and indemnification provisions.',
        'sms' => 'By opting in, you agree to receive text messages from COSPARK and related companies. Message frequency varies. Message and data rates may apply. Reply STOP to opt out or HELP for help.',
    ],

    'social' => [
        'facebook' => env('SOCIAL_FACEBOOK_URL'),
        'instagram' => env('SOCIAL_INSTAGRAM_URL'),
        'linkedin' => env('SOCIAL_LINKEDIN_URL'),
    ],

    // Insurance mega menu: [heading, [[label, product slug | [route, params, fragment]]]]
    'nav_insurance' => [
        ['Vehicle', [
            ['Car', 'car'], ['Motorcycle', 'motorcycle'], ['Boat & Watercraft', 'boat'],
            ['Classic & Collector Auto', 'classic-auto'], ['ATV & Off-Road', 'atv'],
            ['RV & Motor Home', 'rv'], ['Other Recreational Vehicle', 'other-rec'],
            ['All Vehicle Insurance', ['insurance.index', [], 'vehicle']],
        ]],
        ['Property', [
            ['Homeowners', 'homeowners'], ['Condo', 'condo'], ['Renters', 'renters'], ['Flood', 'flood'],
            ['Landlord', 'landlord'], ['Mobile & Manufactured Home', 'mobile-home'],
            ['Vacation Homes', 'vacation-home'], ['Vacant Homes', 'vacant-home'], ['Earthquake', 'earthquake'],
            ['All Property', ['insurance.index', [], 'property']],
        ]],
        ['Life', [
            ['Life Insurance', 'life'], ['Term Life', 'term-life'], ['Whole Life', 'whole-life'], ['Universal Life', 'universal-life'],
        ]],
        ['Commercial', [
            ['Business Insurance', 'business'], ['Commercial Auto', 'commercial-auto'],
            ['Commercial Property', 'commercial-property'], ["Workers' Compensation", 'workers-comp'],
            ['General Liability', 'general-liability'], ['By Industry', 'by-industry'],
        ]],
        ['Bundle & Save', [['Auto & Home', 'auto-home'], ['Auto & Renters', 'auto-renters']]],
        ['Financial Products', [['Financial Solutions', 'financial-solutions'], ['Asset Management', 'asset-management']]],
        ['Partner Products', [['Pet', 'pet'], ['Electronic Device Protection', 'device-protection'], ['Medicare Products', 'medicare']]],
        ['More', [
            ['Personal Umbrella', 'umbrella'],
            ['Insurance in Your State', ['insurance.index', [], 'states']],
            ['View All Products', ['insurance.index', [], null]],
        ]],
    ],

    // Get a Quote dropdown (SOW: Get a Quote options)
    'quote_types' => [
        'auto' => 'Auto',
        'home' => 'Home',
        'renters' => 'Renters',
        'auto-home' => 'Auto & Home',
        'auto-renters' => 'Auto & Renters',
        'life' => 'Life',
        'business' => 'Business',
        'condo' => 'Condo',
    ],

    'us_states' => ['AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY'],

    'us_state_names' => ['Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota', 'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey', 'New Mexico', 'New York', 'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming'],

    'appointment_times' => ['9:00 AM', '10:30 AM', '1:00 PM', '2:30 PM', '3:30 PM', 'After hours — request'],
    // Office days for business-hours slots (ISO: 1 = Monday); other days take the after-hours request only
    'appointment_office_days' => [1, 2, 3, 4],

    'appointment_reasons' => ['New insurance quote', 'Review my policies', 'Membership & tokens', 'Claims help', 'Producer opportunity', 'Vacation planning', 'Other'],

    // Max upload size per file in kilobytes (prototype copy: "up to 10 MB each")
    'upload_max_kb' => 10240,
    'upload_mimes' => 'pdf,jpg,jpeg,png,doc,docx,heic',
];
