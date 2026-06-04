<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tap Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Tap is a unified payment platform supporting KNET, Mada, Visa,
    | Mastercard, Apple Pay, STC Pay, Tabby, Tamara, and more.
    |
    | API Docs: https://developers.tap.company
    |
    */

    'merchant_id' => env('TAP_MERCHANT_ID', ''),

    'secret_key' => env('TAP_SECRET_KEY', ''),

    'public_key' => env('TAP_PUBLIC_KEY', ''),

    'sandbox' => env('TAP_SANDBOX_MODE', true),

    'currency' => env('TAP_CURRENCY', 'SAR'),

    'locale' => env('TAP_LOCALE', 'en'),

    'routes' => [
        'prefix' => env('TAP_ROUTE_PREFIX', 'tap'),
        'middleware' => ['web'],
    ],

    'api_urls' => [
        'sandbox' => 'https://api.tap.company/v2/',
        'production' => 'https://api.tap.company/v2/',
    ],

    'redirect' => [
        'url' => env('TAP_REDIRECT_URL', '/tap/callback'),
    ],

    'webhook' => [
        'url' => env('TAP_WEBHOOK_URL', '/tap/webhook'),
    ],
];
