<?php

declare(strict_types=1);

return [
    /*
     | AI Provider — prism-php/prism provider name (deepseek, anthropic, openai, gemini, ollama...)
     | Set AI_PROVIDER in .env to switch providers without code changes.
     */
    'provider' => env('AI_PROVIDER', 'deepseek'),

    /*
     | Models used per task type.
     | For DeepSeek: 'deepseek-chat' covers all text tasks.
     | For Anthropic: 'claude-haiku-4-5' (fast), 'claude-sonnet-4-5' (smart/vision).
     */
    'fast_model' => env('AI_FAST_MODEL', 'deepseek-chat'),
    'smart_model' => env('AI_SMART_MODEL', 'deepseek-chat'),

    /*
     | Whether the configured provider supports vision (image analysis).
     | DeepSeek = false, Anthropic/OpenAI/Gemini = true
     */
    'vision_enabled' => (bool) env('AI_VISION_ENABLED', false),

    'cache_ttl' => (int) env('AI_CACHE_TTL', 86400),

    'rate_limits' => [
        'free' => (int) env('AI_RATE_LIMIT_FREE', 0),
        'pro' => (int) env('AI_RATE_LIMIT_PRO', 50),
        'business' => (int) env('AI_RATE_LIMIT_BUSINESS', 500),
        'enterprise' => null,
    ],
];
