<?php

// Payments page copy. [label, type, default, hint?] — see App\Support\Content.
return [
    'name' => 'Payments',
    'fields' => [
        'meta_title' => ['SEO title', 'text', 'Payments — COSPARK Insurance Group'],
        'meta_description' => ['SEO description', 'textarea', 'Pay membership dues and insurance premiums, buy Vacation Tokens and manage billing with COSPARK.'],

        'hero_title' => ['Hero · title', 'textarea', 'Pay, track and manage billing.'],
        'hero_lead' => ['Hero · intro', 'textarea', 'Membership dues and insurance premiums are billed separately, so you always know what each payment covers.'],
        'hero_cta_primary' => ['Hero · main button', 'text', 'Sign in to pay'],
        'hero_cta_secondary' => ['Hero · second button', 'text', 'Create an account'],

        'cards' => ['Payment types · 3 cards', 'lines', [
            'Membership dues | Associate {associate.fee}/mo · Premium {premium.fee}/mo · Sub-accounts {sub_account.fee}/mo. Pay monthly, quarterly, bi-annually or annually. Dues Tokens are credited after each successful payment. | Pay dues',
            'Insurance payments | Choose a policy, see the amount and due date, pay and download your receipt. Eligible annual premium earns Insurance Tokens. | Pay a policy',
            'Vacation Tokens | Buy VT at $1 face value each to use toward eligible stays at COSPARK vacation properties. | Buy Vacation Tokens',
        ], 'One card per line: Title | Text | Link text (keep 3 lines: dues, insurance, vacation tokens).'],

        'how_eyebrow' => ['How it works · eyebrow', 'text', 'How payments work'],
        'how_title' => ['How it works · title', 'textarea', 'Secure from start to receipt.'],
        'how_steps' => ['How it works · steps', 'lines', [
            'Choose what to pay | Dues, a policy premium or Vacation Tokens.',
            "Review the amount | See what's due, the period it covers and any tokens you'll earn.",
            'Pick a payment method | Use a saved card or bank account, or add a new one.',
            'Pay & confirm | Get an on-screen confirmation, an email/text receipt and a downloadable invoice.',
        ], 'One step per line: Title | Text'],

        'tools_title' => ['Billing tools · title', 'text', 'Billing tools in your dashboard'],
        'tools' => ['Billing tools · list', 'lines', ['Payment history & invoices', 'Saved payment methods with a default', 'Recurring billing for dues', 'Alerts for failed or late payments and expiring cards', 'Retry a failed payment or switch methods in one click']],
        'tools_notice' => ['Billing tools · security note', 'textarea', 'COSPARK never stores your full card number. Payments are processed by a PCI-compliant payment provider.'],
    ],
];
