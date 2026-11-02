<?php

// Claims help page copy (claim-type guidance is edited under Claim types). [label, type, default, hint?]
return [
    'name' => 'Claims help',
    'fields' => [
        'meta_title' => ['SEO title', 'text', 'Claims Center — COSPARK Insurance Group'],
        'meta_description' => ['SEO description', 'textarea', 'Report a claim, check claim status and get step-by-step guidance after an auto, home, business or life loss.'],

        'hero_title' => ['Hero · title', 'textarea', "We're here when it matters most."],
        'hero_lead' => ['Hero · intro', 'textarea', 'Report a claim online any time, check its progress from your dashboard and get clear guidance on what to do next.'],
        'hero_cta_primary' => ['Hero · main button', 'text', 'Report a claim'],
        'hero_cta_secondary' => ['Hero · second button', 'text', 'View a claim'],

        'quick_cards' => ['Quick links · 4 cards', 'lines', [
            'Report a claim | File online in about 10 minutes.',
            'Track a claim | See status, messages and required documents.',
            'Roadside assistance | Towing, jump-starts and lockouts, 24/7.',
            'Glass claims | Chips and cracked windshields.',
        ], 'One card per line: Title | Text (keep 4 lines: report, track, roadside, glass).'],

        'steps_eyebrow' => ['After an incident · eyebrow', 'text', 'After an incident'],
        'steps_title' => ['After an incident · title', 'textarea', 'What to do first.'],
        'steps_lead' => ['After an incident · intro', 'textarea', 'Follow these steps in order. They protect you and make your claim faster.'],
        'steps' => ['After an incident · steps', 'lines', [
            'Get safe and get help | Move to safety and call 911 if anyone is hurt.',
            "Prevent further damage | Turn off water, cover openings and secure the area if it's safe to do so.",
            'Document everything | Take photos and video, note names, plates, witnesses and a police report number.',
            'Report the claim | File online or call us. Have your policy number ready.',
            'Work with your adjuster | Upload requested documents and track progress in your dashboard.',
        ], 'One step per line: Title | Text'],

        'types_eyebrow' => ['Claim types · eyebrow', 'text', 'Claim types'],
        'types_title' => ['Claim types · title', 'textarea', 'Guidance for your claim.'],
        'types_lead' => ['Claim types · intro', 'textarea', "Choose a claim type for the immediate actions, steps, documents and photos you'll need."],

        'help_cards' => ['Roadside / medical / catastrophe cards', 'lines', [
            'Roadside assistance | Stuck on the road? | Eligible auto policies include towing, jump-starts, flat-tire changes, fuel delivery and lockout help. | Call',
            'Medical providers | Treating a policyholder? | Submit bills and records for an existing claim, or request claim and coverage information. | Provider support',
            'Catastrophe center | Severe weather in your area? | Storm, flood and wildfire updates, temporary housing help and priority claim handling. | Prepare & recover',
        ], 'One card per line: Tag | Title | Text | Link text (keep 3 lines: roadside, medical, catastrophe). The roadside link adds the roadside phone from Settings.'],

        'faq_eyebrow' => ['FAQs · eyebrow', 'text', 'Questions'],
        'faq_title' => ['FAQs · title', 'text', 'Claims FAQs'],
        'faqs' => ['FAQs', 'lines', [
            'How long does a claim take? | Simple glass and auto claims often close within days. Property and liability claims can take longer depending on inspections and documents.',
            "Will filing a claim raise my premium? | It depends on fault, claim type and your carrier's rules. Your agent can explain what to expect before you file.",
            "Can I file for someone else? | Yes. Enter the policyholder's details and your relationship. We may contact the policyholder to confirm.",
            'Where do I upload more documents? | Open the claim in your member dashboard and use "Upload documents" next to any outstanding item.',
        ], 'One FAQ per line: Question | Answer'],
    ],
];
