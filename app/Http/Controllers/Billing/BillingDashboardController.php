<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BillingDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $subscription = $user->subscription('default');

        return Inertia::render('Billing/Dashboard', [
            'plan' => $user->plan_tier->value,
            'planName' => $user->plan_tier->label(),
            'onTrial' => $user->onTrial(),
            'trialEndsAt' => $user->trial_ends_at?->toDateString(),
            'subscribed' => $subscription !== null && $subscription->active(),
            'cancelledAt' => $subscription?->ends_at?->toDateString(),
            'renewsAt' => $subscription?->asStripeSubscription()->current_period_end ?? null,
        ]);
    }
}
