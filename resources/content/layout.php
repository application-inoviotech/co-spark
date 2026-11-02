<?php

// Header menus and footer shared by every public page. Links are "Label | /path" (paths may
// include ?query and #fragment). [label, type, default, hint?] — see App\Support\Content.
$links = 'One link per line: Label | /path';

return [
    'name' => 'Header & footer',
    'fields' => [
        'nav_claims' => ['Header · Claims menu', 'lines', [
            'Claims Help | /claims', 'Roadside Assistance | /claims#roadside', 'Auto Claims | /claims?type=auto',
            'Glass Claims | /claims?type=glass', 'Home Claims | /claims?type=home', 'Business Claims | /claims?type=business',
            'Medical Provider | /claims#medical', 'Report a Claim | /claims/file', 'View Claim | /dashboard#claims',
            'Catastrophe Center | /claims#catastrophe',
        ], $links],
        'nav_resources' => ['Header · Resources menu', 'lines', [
            'Auto Insurance Questions | /resources?cat=auto', 'Life Insurance Questions | /resources?cat=life',
            'Business Insurance Questions | /resources?cat=business', 'Home Insurance Questions | /resources?cat=home',
            'All Insurance Questions | /resources?cat=faq', 'Insurance Articles | /resources',
            'Plan & Prep | /resources?cat=plan', 'Real Stories | /resources?cat=stories',
        ], $links],
        'nav_payments' => ['Header · Payments menu', 'lines', [
            'Membership Dues | /payments#dues', 'Insurance Payments | /payments#insurance', 'Vacation Token Purchase | /payments#vt',
            'Payment History | /dashboard#payments', 'Billing & Invoices | /dashboard#payments', 'Manage Payment Methods | /dashboard#methods',
        ], $links],
        'mega_note' => ['Header · Insurance menu note', 'text', 'Not sure what you need? A COSPARK agent can help.'],
        'mega_cta' => ['Header · Insurance menu link', 'text', 'Start a quote'],

        'footer_tagline' => ['Footer · tagline', 'text', 'Protect today. Build tomorrow.'],
        'footer_meeting' => ['Footer · meeting link', 'text', 'Request a private meeting'],
        'footer_quick' => ['Footer · Quick links', 'lines', [
            'Home | /', 'Insurance | /insurance', 'Get a Quote | /quote', 'Claims | /claims', 'Resources | /resources',
            'Vacation Planning | /ecosystem#vacations', 'Become a Producer | /producer', 'Membership | /membership', 'Contact Us | /contact',
        ], $links],
        'footer_ecosystem' => ['Footer · COSPARK ecosystem links', 'lines', [
            'Business Partners | /ecosystem#partners', 'COSPARK Eateries | /ecosystem#partners',
            'Keys Cove Luxury Vacations | /ecosystem#vacations', 'Fish Camp | /ecosystem#vacations',
        ], $links],
        'footer_legal' => ['Footer · Legal links', 'lines', [
            'Privacy Policy | /legal#privacy', 'Terms & Conditions | /legal#terms', 'Disclaimers | /legal#disclaimers', 'SMS Terms | /legal#sms',
        ], $links],
        'footer_disclaimer' => ['Footer · disclaimer', 'textarea', 'Insurance products are offered through COSPARK Insurance Group, DBA Skrelja Farmers Insurance Group, and are subject to underwriting, availability and policy terms. COSPARK membership and token programs are separate from insurance coverage; tokens have no cash value except as described in the applicable program terms.'],
    ],
];
