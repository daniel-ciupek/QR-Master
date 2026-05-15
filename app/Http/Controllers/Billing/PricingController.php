<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Enums\PlanTier;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PricingController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Billing/Pricing', [
            'currentPlan' => $user?->plan_tier->value ?? PlanTier::Free->value,
            'onTrial' => $user?->onTrial() ?? false,
            'trialEndsAt' => $user?->trial_ends_at?->toDateString(),
        ]);
    }
}
