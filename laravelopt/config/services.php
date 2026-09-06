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

    /*
    |--------------------------------------------------------------------------
    | Anthropic (Claude) — генерация SEO-текстов категорий
    |--------------------------------------------------------------------------
    |
    | Используется командой `php artisan optech:seo:generate`. Обращение к API
    | идёт обычным Http-клиентом Laravel, без SDK.
    |
    */

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
        'version' => env('ANTHROPIC_VERSION', '2023-06-01'),
        'endpoint' => env('ANTHROPIC_ENDPOINT', 'https://api.anthropic.com/v1/messages'),
        // Секунды: генерация с веб-поиском идёт заметно дольше обычного запроса.
        'timeout' => (int) env('ANTHROPIC_TIMEOUT', 300),
    ],

];
