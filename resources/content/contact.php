<?php

// Contact page copy (address, phone and email come from Admin → Settings). [label, type, default, hint?]
return [
    'name' => 'Contact',
    'fields' => [
        'meta_title' => ['SEO title', 'text', 'Contact Us — COSPARK Insurance Group'],
        'meta_description' => ['SEO description', 'textarea', 'Contact COSPARK Insurance Group in Rochester, MI by phone, email or appointment.'],

        'hero_title' => ['Hero · title', 'textarea', "Let's talk."],
        'hero_lead' => ['Hero · intro', 'textarea', 'Questions about coverage, membership or a claim? Send a message or book a time with the team.'],

        'form_title' => ['Form · title', 'text', 'Send us a message'],
        'form_note' => ['Form · note next to the button', 'text', 'We reply within one business day.'],
        'form_button' => ['Form · button', 'text', 'Send message'],
        'done_title' => ['Form · sent title', 'text', 'Message sent.'],
        'done_text' => ['Form · sent text', 'text', "We'll reply within one business day."],

        'hours' => ['Sidebar · hours', 'textarea', "Monday–Thursday\n9:00 AM–4:00 PM"],
        'appt_button' => ['Sidebar · appointment button', 'text', 'Schedule an appointment'],
    ],
];
