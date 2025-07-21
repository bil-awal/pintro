<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Go Transaction Service
    |--------------------------------------------------------------------------
    |
    | Configuration for the external Go transaction microservice.
    |
    */

    'go_transaction' => [
        // Base URL including API prefix
        'base_url' => env('GO_TRANSACTION_SERVICE_URL', 'http://localhost:8080/api/v1'),

        // Request timeout in seconds
        'timeout' => env('GO_TRANSACTION_TIMEOUT', 30),

        // API key header value
        'api_key' => env('GO_TRANSACTION_API_KEY', '8c100781e252cc0a9c588ea6bcbd60d750b13b42957276415895b028d24427e3'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Midtrans Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Configuration for the Midtrans payment gateway integration.
    |
    */

    'midtrans' => [
        'server_key'     => env('MIDTRANS_SERVER_KEY', ''),
        'client_key'     => env('MIDTRANS_CLIENT_KEY', ''),
        'environment'    => env('MIDTRANS_ENVIRONMENT', 'sandbox'),
        'is_production'  => filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN),
        'is_sanitized'   => filter_var(env('MIDTRANS_IS_SANITIZED', true), FILTER_VALIDATE_BOOLEAN),
        'is_3ds'         => filter_var(env('MIDTRANS_IS_3DS', true), FILTER_VALIDATE_BOOLEAN),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Mail & Notification Services
    |--------------------------------------------------------------------------
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
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
