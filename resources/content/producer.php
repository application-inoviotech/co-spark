<?php

// Become a Producer page copy. [label, type, default, hint?] — see App\Support\Content.
return [
    'name' => 'Become a producer',
    'fields' => [
        'meta_title' => ['SEO title', 'text', 'Become a Producer — COSPARK Insurance Group'],
        'meta_description' => ['SEO description', 'textarea', 'Join COSPARK Insurance Group as a licensed producer — 75% commission, 2,000-token bonus and a 10% vacation discount.'],

        'hero_title' => ['Hero · title', 'textarea', 'Build More Than a Book of Business.'],
        'hero_lead' => ['Hero · intro', 'textarea', 'Join COSPARK Insurance Group and become part of a platform built around insurance, member value and additional producer opportunities.'],
        'hero_cta_primary' => ['Hero · main button', 'text', 'Apply now'],
        'hero_cta_secondary' => ['Hero · second button', 'text', 'Producer sign in'],

        'stats' => ['Stats', 'lines', ['75% | Commission', '2,000 | Token bonus', '10% | Vacation discount'], 'One stat per line: Value | Label'],

        'benefits_eyebrow' => ['Benefits · eyebrow', 'text', 'The opportunity'],
        'benefits_title' => ['Benefits · title', 'textarea', 'What producers receive.'],
        'benefits_lead' => ['Benefits · intro', 'textarea', 'Clear terms, a dedicated producer portal and benefits that grow with your production.'],
        'benefits' => ['Benefits · cards', 'lines', [
            "75% commission | On applicable insurance you sell within the company's book of business.",
            "2,000-token bonus | Credited once you're hired and licensed. Redeemable after 12 months of writing insurance with COSPARK while maintaining the {associate.fee} membership.",
            '10% vacation discount | For you, your family and friends at participating COSPARK vacation properties.',
            'Additional income programs | Top-performer programs and opportunities across the COSPARK ecosystem.',
            'Auto allowance | Available to producers who reach qualifying production levels.',
            'Leads & appointments | Assigned leads and appointments delivered straight to your portal.',
        ], 'One card per line: Title | Text'],

        'req_eyebrow' => ['Requirements · eyebrow', 'text', 'Requirements'],
        'req_title' => ['Requirements · title', 'textarea', 'Before you apply.'],
        'req_lead' => ['Requirements · intro', 'textarea', "Read these terms carefully — they're part of the producer agreement."],
        'requirements' => ['Requirements · list', 'lines', [
            "An active insurance license (or in progress) for the lines you'll write",
            'A maintained COSPARK Associate membership at {associate.fee}/month',
            '12 months of writing insurance with COSPARK to redeem the token bonus',
            'The book of business is owned by the company',
            'Employment and non-compete terms apply as described in the producer agreement',
        ]],
    ],
];
