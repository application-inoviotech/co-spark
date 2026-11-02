<?php

// Membership page copy (plan cards, prices and the compare table come from the membership plans).
// [label, type, default, hint?] — see App\Support\Content.
return [
    'name' => 'Membership',
    'fields' => [
        'meta_title' => ['SEO title', 'text', 'Membership & Tokens — COSPARK Insurance Group'],
        'meta_description' => ['SEO description', 'textarea', 'Compare Free, Associate and Premium COSPARK memberships and learn how Dues, Insurance and Vacation Tokens work.'],

        'hero_title' => ['Hero · title', 'textarea', 'Membership Designed Around Your Needs.'],
        'hero_lead' => ['Hero · intro', 'textarea', 'Keep every policy in one digital account for free, or join the COSPARK token program as an Associate or Premium member.'],

        'plans_eyebrow' => ['Plans · eyebrow', 'text', 'Choose your level'],
        'plans_note' => ['Plans · note under cards', 'textarea', 'Paid memberships activate after email verification and a successful first payment. Recurring billing can be changed or cancelled from your dashboard.'],
        'plan_free_features' => ['Plans · Free card list', 'lines', ['Member account', 'Digital insurance information', 'Insurance policy management', 'No membership tokens', 'Upgrade anytime'], 'Placeholders: {associate.fee} {premium.dt} {premium.it} {premium.pct} {sub_account.fee}'],
        'plan_associate_features' => ['Plans · Associate card list', 'lines', ['{associate.dt} Dues Tokens per month after successful payment', 'Insurance Tokens = {associate.it} of eligible annual premium', 'Multiple policies', 'Full member dashboard & QR benefits'], 'Placeholders: {associate.fee} {premium.dt} {premium.it} {premium.pct} {sub_account.fee}'],
        'plan_premium_features' => ['Plans · Premium card list', 'lines', ['{premium.dt} Dues Tokens per month after successful payment', 'Insurance Tokens = {premium.it} of eligible annual premium', 'Multiple policies', 'Sub-accounts for family, employees or groups — {sub_account.fee}/mo each', 'Priority partner benefits'], 'Placeholders: {associate.fee} {premium.dt} {premium.it} {premium.pct} {sub_account.fee}'],
        'compare_title' => ['Compare table · title', 'text', 'Compare plans'],
        'compare_rows' => ['Compare table · rows', 'lines', [
            'Monthly price | {free.fee} | {associate.fee} | {premium.fee}',
            'Digital policy management | ✓ | ✓ | ✓',
            'Multiple policies | ✓ | ✓ | ✓',
            'Dues Tokens (DT) per month | — | {associate.dt} | {premium.dt}',
            'Insurance Tokens (IT) | — | {associate.it} of eligible annual premium | {premium.it} of eligible annual premium',
            'Vacation Token (VT) purchases | — | ✓ | ✓',
            'QR partner benefits | — | ✓ | Priority',
            'Token transfers | — | Eligible | Eligible',
            'Sub-accounts | — | — | {sub_account.fee}/mo per account',
        ], 'One row per line: Feature | Free | Associate | Premium. '.'Placeholders: {associate.fee} {premium.dt} {premium.it} {premium.pct} {sub_account.fee}'],

        'tokens_eyebrow' => ['Tokens · eyebrow', 'text', 'The token system'],
        'tokens_title' => ['Tokens · title', 'textarea', "Three balances.\nOne clear ledger."],
        'tokens_lead' => ['Tokens · intro', 'textarea', 'Your wallet tracks each token type separately, with a full history of how it was earned, used, transferred or forfeited.'],
        'token_dt' => ['Tokens · DT tile', 'lines', ['DT · Dues Tokens | {associate.dt}–{premium.dt} | Credited each month only after a successful membership payment. Associate {associate.dt}, Premium {premium.dt}, Free {free.dt}.'], 'One line: Label | Big value | Text. '.'Placeholders: {associate.fee} {premium.dt} {premium.it} {premium.pct} {sub_account.fee}'],
        'token_it' => ['Tokens · IT tile', 'lines', ["IT · Insurance Tokens | {associate.pct}–{premium.pct}% | Calculated on eligible annual premium and tracked per policy and policy year. Cancelling mid-year forfeits that year's IT; completed years remain."], 'One line: Label | Big value | Text. '.'Placeholders: {associate.fee} {premium.dt} {premium.it} {premium.pct} {sub_account.fee}'],
        'token_vt' => ['Tokens · VT tile', 'lines', ['VT · Vacation Tokens | $1 | Purchased by members at $1 face value each and used toward eligible vacation stays. VT accrues value under the program terms.*'], 'One line: Label | Big value | Text'],
        'estimator_label' => ['Token estimator · label', 'text', 'Estimate your Insurance Tokens'],
        'estimator_title' => ['Token estimator · title', 'text', 'What could your policies earn?'],
        'tokens_footnote' => ['Tokens · footnote', 'textarea', '*The VT accrual rate and the 60-month redemption rules are being finalized with COSPARK and will be published in the program terms. Estimates are illustrative only.'],

        'journey_eyebrow' => ['60-month journey · eyebrow', 'text', 'The 60-month journey'],
        'journey_title' => ['60-month journey · title', 'textarea', "Built over time.\nRedeemed by the rules."],
        'journey_lead' => ['60-month journey · intro', 'textarea', "Tokens build across a 60-month cycle. Your dashboard shows where you are, what's eligible and what's still required."],
        'journey_steps' => ['60-month journey · steps', 'lines', [
            'Month 1 — Join | Verify your email and complete your first membership payment. Your membership activates.',
            'Every month — Dues Tokens | Each successful dues payment credits {associate.dt} or {premium.dt} DT to your wallet.',
            'Each policy year — Insurance Tokens | Eligible annual premium earns IT, tracked by policy and policy year.',
            'Anytime — Use benefits | Present your QR code at participating partners and plan vacations with VT.',
            'Month 60 — Redemption | Access redeemable value according to the program terms and your eligibility.',
        ], 'One step per line: Title | Text. Steps 1–2 show as done, step 3 as current.'],

        'subs_eyebrow' => ['Sub-accounts · eyebrow', 'text', 'Premium sub-accounts'],
        'subs_title' => ['Sub-accounts · title', 'textarea', 'One membership for the whole group.'],
        'subs_lead' => ['Sub-accounts · intro', 'textarea', 'Premium members can add family, employees or group members for {sub_account.fee} a month each. Every sub-account gets its own login while staying linked to the main account.'],
        'subs_points' => ['Sub-accounts · list', 'lines', ['The main member manages billing and permissions', 'Sub-accounts access permitted membership and partner benefits', 'Add, pause or remove sub-accounts from your dashboard', 'The main member stays the point of contact']],

        'faq_eyebrow' => ['FAQs · eyebrow', 'text', 'Questions'],
        'faq_title' => ['FAQs · title', 'text', 'Membership FAQs'],
        'faqs' => ['FAQs', 'lines', [
            'Is membership required to buy insurance? | No. You can buy insurance without a paid membership. A free account still gives you digital access to your policies.',
            "When are Dues Tokens credited? | Only after a successful membership payment. Failed or pending payments don't issue DT; you can retry or change your payment method from your dashboard.",
            'What happens to Insurance Tokens if I cancel a policy? | Insurance Tokens for the current policy year of that policy are forfeited. Tokens from previously completed policy years remain in your wallet.',
            "Can I transfer tokens? | Eligible members can transfer tokens to other registered members. Transfers are reviewed and recorded in both members' ledgers.",
            'Are membership dues and insurance payments the same? | No. Dues and insurance premiums are billed and tracked separately so you always know what each payment covers.',
        ], 'One FAQ per line: Question | Answer'],

        'cta_title' => ['Bottom banner · title', 'text', 'Start free. Upgrade anytime.'],
        'cta_primary' => ['Bottom banner · main button', 'text', 'Join COSPARK'],
        'cta_secondary' => ['Bottom banner · second button', 'text', 'Ask a question'],
    ],
];
