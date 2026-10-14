<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Gateway
    |--------------------------------------------------------------------------
    |
    | driver: "log" records messages in whatsapp_messages without sending them
    | (the default until the owner s Node.js service exists); "node" posts to
    | the url below. Switching is a .env change only.
    |
    */

    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'log'),
        'url' => env('WHATSAPP_URL', ''),
        'token' => env('WHATSAPP_TOKEN'),
        'timeout' => (int) env('WHATSAPP_TIMEOUT', 15),
        'admin_number' => env('ADMIN_WHATSAPP_NUMBER', ''),
    ],

];
