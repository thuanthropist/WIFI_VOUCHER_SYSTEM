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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mobile Money Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Configuration for the Tanzanian mobile money aggregators the payment
    | gateway manager (App\Services\PaymentGatewayManager) can drive. Only
    | one is active at a time, chosen via PAYMENT_GATEWAY.
    |
    */

    'payment' => [
        'default' => env('PAYMENT_GATEWAY', 'selcom'),
    ],

    'selcom' => [
        'vendor_id' => env('SELCOM_VENDOR_ID'),
        'api_key' => env('SELCOM_API_KEY'),
        'api_secret' => env('SELCOM_API_SECRET'),
        'base_url' => env('SELCOM_BASE_URL', 'https://apigw.selcommobile.com'),
        'callback_secret' => env('SELCOM_CALLBACK_SECRET'),
    ],

    'clickpesa' => [
        'client_id' => env('CLICKPESA_CLIENT_ID'),
        'api_key' => env('CLICKPESA_API_KEY'),
        'base_url' => env('CLICKPESA_BASE_URL', 'https://api.clickpesa.com'),
        'webhook_secret' => env('CLICKPESA_WEBHOOK_SECRET'),
    ],

    'azampay' => [
        'client_id' => env('AZAMPAY_CLIENT_ID'),
        'client_secret' => env('AZAMPAY_CLIENT_SECRET'),
        'app_name' => env('AZAMPAY_APP_NAME'),
        'base_url' => env('AZAMPAY_BASE_URL', 'https://authenticator-sandbox.azampay.co.tz'),
        'checkout_url' => env('AZAMPAY_CHECKOUT_URL', 'https://sandbox.azampay.co.tz'),
        'webhook_secret' => env('AZAMPAY_WEBHOOK_SECRET'),
    ],

];
