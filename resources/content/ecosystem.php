<?php

// Ecosystem & vacations page copy. [label, type, default, hint?] — see App\Support\Content.
return [
    'name' => 'Ecosystem & vacations',
    'fields' => [
        'meta_title' => ['SEO title', 'text', 'COSPARK Ecosystem & Vacations — COSPARK Insurance Group'],
        'meta_description' => ['SEO description', 'textarea', 'Partner businesses, eateries and luxury vacation properties connected to COSPARK membership.'],

        'hero_title' => ['Hero · title', 'textarea', 'Insurance Is Only the Beginning.'],
        'hero_lead' => ['Hero · intro', 'textarea', 'COSPARK connects its insurance platform with a growing collection of businesses, destinations and member experiences.'],

        'vac_eyebrow' => ['Vacations · eyebrow', 'text', 'Vacation planning'],
        'vac_title' => ['Vacations · title', 'textarea', 'Stays worth planning for.'],
        'vac_lead' => ['Vacations · intro', 'textarea', "Members use tokens toward eligible stays. Requests need at least 90 days' notice and are subject to availability."],
        'vac_cta' => ['Vacations · card button', 'text', 'Request a stay'],

        'partners_eyebrow' => ['Partners · eyebrow', 'text', 'Partners & benefits'],
        'partners_title' => ['Partners · title', 'textarea', 'Benefits in your pocket.'],
        'partners_lead' => ['Partners · intro', 'textarea', 'Show your member QR code at participating partners. Eligibility and your balance are checked instantly.'],
        'partners_cta' => ['Partners · card link', 'text', 'Visit partner'],

        'qr_eyebrow' => ['QR redemption · eyebrow', 'text', 'How QR redemption works'],
        'qr_title' => ['QR redemption · title', 'textarea', 'Scan. Verify. Enjoy.'],
        'qr_steps' => ['QR redemption · steps', 'lines', [
            'Open your QR | From your dashboard on any phone.',
            'Partner scans it | The QR, your membership and eligibility are validated.',
            'Credit is applied | Your balance is updated and a reference ID is recorded.',
            "You're notified | A receipt arrives by email or text.",
        ], 'One step per line: Title | Text'],
    ],
];
