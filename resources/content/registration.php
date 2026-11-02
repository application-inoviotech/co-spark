<?php

// Copy shown during member sign-up. [label, type, default, hint?] — see App\Support\Content.
return [
    'name' => 'Registration',
    'fields' => [
        'sms_consent' => ['SMS consent (opt-in checkbox text)', 'textarea', 'Yes, send me text messages from COSPARK and related companies about my account, policies, payments and offers. Message & data rates may apply. Reply STOP to opt out.', 'Shown next to the SMS opt-in checkbox when a member signs up.'],
    ],
];
