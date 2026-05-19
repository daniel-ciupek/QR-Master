<?php

declare(strict_types=1);

use App\Enums\PlanTier;

/*
|--------------------------------------------------------------------------
| Subscription Plans
|--------------------------------------------------------------------------
|
| Maps PlanTier enum values to Stripe price IDs and plan metadata.
| Set STRIPE_PRICE_PRO, STRIPE_PRICE_BUSINESS, etc. in .env.
|
*/
return [
    PlanTier::Free->value => [
        'name' => 'Free',
        'stripe_price_id' => null,
        'monthly_price_pln' => 0,
    ],

    PlanTier::Pro->value => [
        'name' => 'Pro',
        'stripe_price_id' => env('STRIPE_PRICE_PRO'),
        'monthly_price_pln' => 4900,
    ],

    PlanTier::Business->value => [
        'name' => 'Business',
        'stripe_price_id' => env('STRIPE_PRICE_BUSINESS'),
        'monthly_price_pln' => 19900,
    ],

    PlanTier::Enterprise->value => [
        'name' => 'Enterprise',
        'stripe_price_id' => env('STRIPE_PRICE_ENTERPRISE'),
        'monthly_price_pln' => 0,
    ],
];
