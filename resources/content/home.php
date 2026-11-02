<?php

// Home page copy. [label, type, default, hint?] — see App\Support\Content.
return [
    'name' => 'Home',
    'fields' => [
        'meta_title' => ['SEO title', 'text', 'COSPARK Insurance Group — Protect What Matters. Build What Comes Next.'],
        'meta_description' => ['SEO description', 'textarea', 'COSPARK Insurance Group brings insurance, digital membership and a growing ecosystem of rewards and benefits together in one connected experience.'],

        'hero_eyebrow' => ['Hero · eyebrow', 'text', 'Insurance × Membership × Future value'],
        'hero_title' => ['Hero · title', 'text', 'Protect What Matters.'],
        'hero_title_highlight' => ['Hero · title (gradient line)', 'text', 'Build What Comes Next.'],
        'hero_lead' => ['Hero · intro', 'textarea', 'COSPARK Insurance Group brings insurance, digital membership and a growing ecosystem of rewards and benefits together in one connected experience.'],
        'hero_cta_primary' => ['Hero · main button', 'text', 'Get a Quote'],
        'hero_cta_secondary' => ['Hero · second button', 'text', 'Explore Membership'],
        'hero_note' => ['Hero · note', 'text', 'Insurance for today. A platform designed with tomorrow in mind.'],

        'marquee_label' => ['Feature strip · label', 'text', 'One connected platform for your insurance journey'],
        'marquee' => ['Feature strip · items', 'lines', ['Insurance Coverage', 'Digital Membership', 'Policy Management', 'Token Rewards', 'Payments', 'Partner Benefits', 'Claims Support', 'Vacation Planning']],

        'connected_label' => ['01 Connected model · section label', 'text', 'The connected model'],
        'connected_eyebrow' => ['01 Connected model · eyebrow', 'text', 'More than insurance'],
        'connected_title' => ['01 Connected model · title', 'textarea', 'Your Insurance Can Become Part of a Bigger Picture.'],
        'connected_lead' => ['01 Connected model · intro', 'textarea', 'With COSPARK, insurance is the foundation. Membership adds a digital layer for managing policies, payments, rewards and access to participating COSPARK benefits.'],
        'connected_prompt' => ['01 Connected model · prompt', 'text', 'Select a part of the model to see how it connects.'],

        'model_label' => ['02 How it works · section label', 'text', 'The COSPARK model'],
        'model_eyebrow' => ['02 How it works · eyebrow', 'text', 'How it works'],
        'model_title' => ['02 How it works · title', 'textarea', "Simple to Join.\nDesigned to Grow With You."],
        'model_lead' => ['02 How it works · intro', 'textarea', 'Monthly membership tokens, annual-premium token allocations and a 60-month redemption mechanism — structured, transparent and tracked inside your member account.'],
        'model_steps' => ['02 How it works · 3 steps', 'lines', [
            'Join | Create your COSPARK membership and bring your insurance experience into one digital account. | Create your account',
            'Build | Depending on your membership, receive monthly Dues Tokens and Insurance Tokens based on your eligible annual premium. | How tokens work',
            "Redeem | Maintain applicable eligibility through the program's 60-month cycle and access your redeemable value according to the program terms. | See the journey",
        ], 'One step per line: Title | Text | Link text (keep 3 lines).'],

        'plans_label' => ['03 Membership · section label', 'text', 'Membership'],
        'plans_eyebrow' => ['03 Membership · eyebrow', 'text', 'Choose your level'],
        'plans_title' => ['03 Membership · title', 'textarea', "Membership Designed\nAround Your Needs."],
        'plans_lead' => ['03 Membership · intro', 'textarea', 'Start with digital access or participate in the COSPARK token program through an Associate or Premium membership.'],
        'plan_free_features' => ['03 Membership · Free card list', 'lines', ['Digital member account', 'Insurance information & policy management', 'Upgrade anytime'], 'Placeholders: {associate.fee} {premium.dt} {premium.it} {premium.pct} {sub_account.fee}'],
        'plan_associate_features' => ['03 Membership · Associate card list', 'lines', ['{associate.dt} Dues Tokens each month', '{associate.pct}% of annual insurance premium → Insurance Tokens', 'Multiple policies, full member dashboard'], 'Placeholders: {associate.fee} {premium.dt} {premium.it} {premium.pct} {sub_account.fee}'],
        'plan_premium_features' => ['03 Membership · Premium card list', 'lines', ['{premium.dt} Dues Tokens each month', '{premium.pct}% of annual insurance premium → Insurance Tokens', 'Family & group sub-accounts ({sub_account.fee}/mo each)', 'Priority partner benefits'], 'Placeholders: {associate.fee} {premium.dt} {premium.it} {premium.pct} {sub_account.fee}'],

        'wallet_label' => ['04 Token wallet · section label', 'text', 'Token wallet'],
        'wallet_title' => ['04 Token wallet · title', 'textarea', "See Your Value.\nFollow Your Journey."],
        'wallet_lead' => ['04 Token wallet · intro', 'textarea', 'Your COSPARK experience gives you a digital view of applicable token activity, membership payments and your redemption journey.'],

        'insurance_label' => ['05 Insurance · section label', 'text', 'Insurance'],
        'insurance_eyebrow' => ['05 Insurance · eyebrow', 'text', 'Cover what matters'],
        'insurance_title' => ['05 Insurance · title', 'textarea', "Protection for\nEvery Part of Life."],
        'insurance_lead' => ['05 Insurance · intro', 'textarea', 'From the home you live in to the vehicle you drive and the business you build, COSPARK provides access to insurance solutions designed around your needs.'],
        'insurance_cards' => ['05 Insurance · 4 cards', 'lines', [
            'Home | Protect Your Place. | Coverage designed around your home and what matters inside it. | Explore home insurance',
            'Auto | Own the Road Ahead. | Insurance solutions for the vehicles that keep life moving. | Explore auto insurance',
            'Health | Protect Your Wellbeing. | Insurance options for your healthcare needs. | Explore health insurance',
            "Commercial | Protect What You've Built. | Coverage options for businesses and commercial needs. | Explore commercial insurance",
        ], 'One card per line: Tag | Title | Text | Link text (keep 4 lines: home, auto, health, commercial).'],
        'insurance_cta' => ['05 Insurance · button', 'text', 'View all insurance products'],

        'policy_label' => ['06 Policies · section label', 'text', 'Policy management'],
        'policy_eyebrow' => ['06 Policies · eyebrow', 'text', 'Your policies. One place.'],
        'policy_title' => ['06 Policies · title', 'textarea', "Everything You Need.\nRight Where You Need It."],
        'policy_lead' => ['06 Policies · intro', 'textarea', 'Manage multiple insurance policies through one connected member experience.'],

        'quote_label' => ['07 Quote · section label', 'text', 'Get a quote'],
        'quote_eyebrow' => ['07 Quote · eyebrow', 'text', "Let's get started"],
        'quote_title' => ['07 Quote · title', 'textarea', "Your Next Policy Starts\nWith One Conversation."],
        'quote_lead' => ['07 Quote · intro', 'textarea', 'Tell us what you need to protect and the COSPARK team will help you take the next step.'],

        'appt_label' => ['08 Appointments · section label', 'text', 'Appointments'],
        'appt_eyebrow' => ['08 Appointments · eyebrow', 'text', 'Talk when it works for you'],
        'appt_title' => ['08 Appointments · title', 'textarea', "Insurance Advice\non Your Schedule."],
        'appt_lead' => ['08 Appointments · intro', 'textarea', 'Book a phone or in-person appointment during office hours — or request an available after-hours time.'],
        'appt_hours' => ['08 Appointments · hours note', 'text', 'Monday–Thursday · 9 AM–4 PM'],

        'eco_label' => ['09 Ecosystem · section label', 'text', 'Ecosystem'],
        'eco_eyebrow' => ['09 Ecosystem · eyebrow', 'text', 'Welcome to the ecosystem'],
        'eco_title' => ['09 Ecosystem · title', 'textarea', 'Insurance Is Only the Beginning.'],
        'eco_lead' => ['09 Ecosystem · intro', 'textarea', 'COSPARK connects its insurance platform with a growing collection of businesses, destinations and member experiences.'],
        'eco_cta' => ['09 Ecosystem · link', 'text', 'Explore the ecosystem'],

        'qr_label' => ['10 Member benefits · section label', 'text', 'Digital member benefits'],
        'qr_eyebrow' => ['10 Member benefits · eyebrow', 'text', 'Benefits in your pocket'],
        'qr_title' => ['10 Member benefits · title', 'textarea', "Your Membership\nTravels With You."],
        'qr_lead' => ['10 Member benefits · intro', 'textarea', 'Participating COSPARK partner programs can connect eligible member benefits with digital delivery through email or text, including QR-based redemption experiences.'],
        'qr_features' => ['10 Member benefits · feature line', 'text', 'QR generation · Delivery · Scanning · Eligibility verification · Redemption history'],
        'qr_cta' => ['10 Member benefits · link', 'text', 'Explore member benefits'],

        'producer_label' => ['11 Producers · section label', 'text', 'Become a producer'],
        'producer_eyebrow' => ['11 Producers · eyebrow', 'text', 'For insurance professionals'],
        'producer_title' => ['11 Producers · title', 'textarea', "Build More Than\na Book of Business."],
        'producer_lead' => ['11 Producers · intro', 'textarea', 'Join COSPARK Insurance Group and become part of a platform built around insurance, member value and additional producer opportunities.'],
        'producer_stats' => ['11 Producers · stats', 'lines', ['75% | Commission', '2,000 | Token bonus', '10% | Vacation discount'], 'One stat per line: Value | Label'],
        'producer_cta_primary' => ['11 Producers · main button', 'text', 'Become a Producer'],
        'producer_cta_secondary' => ['11 Producers · second button', 'text', 'View Producer Benefits'],
        'producer_note' => ['11 Producers · note', 'text', 'Licensing and application workflow handled inside the producer portal'],

        'final_eyebrow' => ['Final CTA · eyebrow', 'text', 'Start your COSPARK journey'],
        'final_title' => ['Final CTA · title', 'textarea', "Protect Today.\nBuild Tomorrow."],
        'final_lead' => ['Final CTA · intro', 'textarea', 'Get your insurance quote, explore membership or connect with the COSPARK team.'],
        'final_pillars' => ['Final CTA · pillars', 'lines', ['Protection', 'Membership', 'Value', 'Possibility']],
    ],
];
