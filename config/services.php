<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Emails and text messages (Klaviyo). When a private key is set, notifications are sent as
    // "COSPARK Email" / "COSPARK SMS" events for Klaviyo flows; without it, Laravel mail is used.
    'klaviyo' => [
        'key' => env('KLAVIYO_PRIVATE_KEY'),
        'sms_list' => env('KLAVIYO_SMS_LIST_ID'), // list that SMS opt-ins are subscribed to
        'revision' => env('KLAVIYO_API_REVISION', '2025-10-15'),
    ],

    // Card payments (Authorize.net). Cards are tokenized in the browser with Accept.js using the
    // public client key; renewals are charged by the daily billing job.
    'authorizenet' => [
        'login_id' => env('AUTHORIZENET_LOGIN_ID'),
        'transaction_key' => env('AUTHORIZENET_TRANSACTION_KEY'),
        'client_key' => env('AUTHORIZENET_CLIENT_KEY'),
        'signature_key' => env('AUTHORIZENET_SIGNATURE_KEY'),
        'sandbox' => env('AUTHORIZENET_ENV', 'sandbox') !== 'production',
    ],

];
