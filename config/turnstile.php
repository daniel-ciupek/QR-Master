<?php

declare(strict_types=1);

return [
    'site_key' => env('CLOUDFLARE_TURNSTILE_SITE_KEY', ''),
    'secret_key' => env('CLOUDFLARE_TURNSTILE_SECRET_KEY', ''),
    'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',

    // Routes (without leading slash) that require Turnstile verification on POST
    'protected_routes' => ['register', 'forgot-password'],

    // Anti-bot: number of hits/min from a single IP before requiring Turnstile on /q/{hash}
    'bot_scan_threshold' => (int) env('TURNSTILE_BOT_SCAN_THRESHOLD', 20),
];
