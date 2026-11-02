<?php

// Insurance products index copy (categories and products are edited under Insurance products/categories).
// [label, type, default, hint?] — see App\Support\Content.
return [
    'name' => 'Insurance products',
    'fields' => [
        'meta_title' => ['SEO title', 'text', 'Insurance Products — COSPARK Insurance Group'],
        'meta_description' => ['SEO description', 'textarea', 'Vehicle, property, life, commercial, financial and partner insurance products from COSPARK Insurance Group.'],

        'hero_title' => ['Hero · title', 'textarea', 'Protection for Every Part of Life.'],
        'hero_lead' => ['Hero · intro', 'textarea', "Browse coverage by category, compare what's included and start a quote when you're ready."],
        'hero_cta_primary' => ['Hero · main button', 'text', 'Get a Quote'],
        'hero_cta_secondary' => ['Hero · second button', 'text', 'Speak to an agent'],
        'card_link' => ['Product card · link text', 'text', 'Learn more'],

        'states_tab' => ['States · tab label', 'text', 'By state'],
        'states_eyebrow' => ['States · eyebrow', 'text', 'Insurance in your state'],
        'states_title' => ['States · title', 'textarea', 'Coverage rules change at the state line.'],
        'states_lead' => ['States · intro', 'textarea', "Choose your state to see what's available and the minimum auto requirements an agent will help you meet."],
        'states_prompt' => ['States · prompt', 'text', 'Select a state to see product availability.'],
    ],
];
