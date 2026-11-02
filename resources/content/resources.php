<?php

// Resources landing copy (articles are edited under Articles). [label, type, default, hint?]
return [
    'name' => 'Resources',
    'fields' => [
        'meta_title' => ['SEO title', 'text', 'Resources — COSPARK Insurance Group'],
        'meta_description' => ['SEO description', 'textarea', 'Insurance guides, FAQs, glossary and articles on auto, home, life, business, claims, billing and COSPARK membership.'],

        'hero_title' => ['Hero · title', 'textarea', 'Insurance, explained clearly.'],
        'hero_lead' => ['Hero · intro', 'textarea', 'Guides, answers and checklists to help you choose coverage, prepare for the unexpected and get the most from membership.'],
        'search_placeholder' => ['Search box placeholder', 'text', 'Search guides, e.g. deductible, flood, tokens'],
        'empty_text' => ['No results · text', 'text', 'No resources match that search. Try another term or'],
        'empty_link' => ['No results · link', 'text', 'ask an agent'],

        'glossary_eyebrow' => ['Glossary · eyebrow', 'text', 'Glossary'],
        'glossary_title' => ['Glossary · title', 'textarea', 'Common terms.'],
        'glossary_lead' => ['Glossary · intro', 'textarea', "Plain-language definitions for the words you'll see on your policy and in your member account."],
        'glossary' => ['Glossary · terms', 'lines', [
            'Premium | The amount you pay for an insurance policy, usually monthly or annually.',
            'Deductible | What you pay out of pocket on a claim before your insurance pays.',
            'Declarations page | The summary page of your policy showing coverages, limits and premium.',
            "Liability | Coverage for injuries or damage you're responsible for causing to others.",
            'Dues Tokens (DT) | Tokens credited monthly after a successful Associate or Premium membership payment.',
            'Insurance Tokens (IT) | Tokens earned on eligible annual insurance premium — {associate.it} for Associate, {premium.it} for Premium.',
            'Vacation Tokens (VT) | Tokens members purchase at $1 face value each for eligible vacation use.',
        ], 'One term per line: Term | Definition. Placeholders: {associate.it} {premium.it} {premium.fee}'],

        'help_cards' => ['Help cards', 'lines', [
            'Still have questions? | Our team is available Monday–Thursday, 9 AM–4 PM. | Contact us | /contact',
            'Billing help | Dues, premiums, receipts and payment methods. | Payment center | /payments',
            'Helpful links | NAIC consumer resources · FEMA flood maps · Michigan DIFS | Visit NAIC | https://content.naic.org/consumer',
        ], 'One card per line: Title | Text | Link text | Link (a /path or a full https:// address)'],
    ],
];
