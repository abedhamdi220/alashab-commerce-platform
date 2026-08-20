<?php

return [
    'meta' => [
        'verify_token' => env('META_VERIFY_TOKEN'),
        'app_secret' => env('META_APP_SECRET'),
        // Fallback only. Prefer the encrypted per-merchant token stored on users.
        'whatsapp_access_token' => env('META_WHATSAPP_ACCESS_TOKEN'),
    ],

    'messenger' => [
        // Fallback only. Prefer the encrypted per-merchant token stored on users.
        'access_token' => env('MESSENGER_ACCESS_TOKEN'),
    ],

    'evolution' => [
        'base_url' => env('EVOLUTION_BASE_URL'),
        'api_key' => env('EVOLUTION_API_KEY'),
        // عنوان Laravel العام الذي يستقبل أحداث Evolution لكل جلسات التجار.
        'webhook_url' => env('EVOLUTION_WEBHOOK_URL', rtrim((string) env('APP_URL'), '/').'/api/webhook/evolution'),
        'webhook_secret' => env('EVOLUTION_WEBHOOK_SECRET'),
        'webhook_header' => env('EVOLUTION_WEBHOOK_HEADER', 'X-Evolution-Webhook-Secret'),
    ],
];
