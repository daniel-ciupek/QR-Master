<?php

declare(strict_types=1);

return [
    'fast_model' => env('AI_FAST_MODEL', 'claude-haiku-4-5'),
    'smart_model' => env('AI_SMART_MODEL', 'claude-sonnet-4-5'),
    'cache_ttl' => (int) env('AI_CACHE_TTL', 86400),

    'rate_limits' => [
        'free' => (int) env('AI_RATE_LIMIT_FREE', 0),
        'pro' => (int) env('AI_RATE_LIMIT_PRO', 50),
        'business' => (int) env('AI_RATE_LIMIT_BUSINESS', 500),
        'enterprise' => null,
    ],
];
