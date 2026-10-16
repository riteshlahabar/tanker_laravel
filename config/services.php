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
        // log | node | scan. "scan" is the owner's Baileys QR-scan
        // service, configured in the whatsapp_scan block below.
        'driver' => env('WHATSAPP_DRIVER', 'log'),
        'url' => env('WHATSAPP_URL', ''),
        'token' => env('WHATSAPP_TOKEN'),
        'timeout' => (int) env('WHATSAPP_TIMEOUT', 15),
        'admin_number' => env('ADMIN_WHATSAPP_NUMBER', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp scan service (Node.js, Baileys)
    |--------------------------------------------------------------------------
    |
    | The owner's microservice: it shows a QR, holds one WhatsApp Web session
    | and sends messages. The panel reads /qr and /status for the scan screen
    | and posts /send for every message. Every call but /health carries the
    | key as an x-api-key header.
    |
    */

    'whatsapp_scan' => [
        'base_url' => rtrim((string) env('WHATSAPP_SCAN_BASE_URL', ''), '/'),
        'api_key' => (string) env('WHATSAPP_SCAN_API_KEY', ''),
        // Generous on purpose: a cold cPanel worker can take a while to wake.
        'timeout' => (int) env('WHATSAPP_SCAN_TIMEOUT', 60),
    ],

];
