<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Enums\PlanTier;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class TeamBillingController extends Controller
{
    public function show(Request $request, Team $team): Response
    {
        Gate::authorize('manageBilling', $team);

        $subscription = $team->subscription('default');
        $tier = $team->plan_tier;

        return Inertia::render('Workspace/Billing', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'plan' => $tier->value,
            'planName' => $tier->label(),
            'onTrial' => $team->onTrial(),
            'trialEndsAt' => $team->trial_ends_at?->toDateString(),
            'subscribed' => $subscription !== null && $subscription->active(),
            'cancelledAt' => $subscription?->ends_at?->toDateString(),
        ]);
    }

    public function subscribe(Request $request, Team $team, string $plan): RedirectResponse
    {
        Gate::authorize('manageBilling', $team);

        $tier = PlanTier::tryFrom($plan);

        if ($tier === null || $tier === PlanTier::Free || $tier === PlanTier::Enterprise) {
            return redirect()->route('workspaces.billing.show', $team);
        }

        $priceId = config("plans.{$tier->value}.stripe_price_id");

        if (! is_string($priceId) || $priceId === '') {
            return redirect()->route('workspaces.billing.show', $team)
                ->with('error', 'Plan not configured. Please contact support.');
        }

        /** @var User $user */
        $user = $request->user();

        if (! $team->hasStripeId()) {
            $team->createAsStripeCustomer([
                'name' => $team->name,
                'email' => $user->email,
                'metadata' => ['team_id' => $team->id],
            ]);
        }

        $checkout = $team
            ->newSubscription('default', $priceId)
            ->allowPromotionCodes()
            ->checkout([
                'success_url' => route('workspaces.billing.show', $team).'?subscribed=1',
                'cancel_url' => route('workspaces.billing.show', $team),
                'automatic_tax' => ['enabled' => true],
                'tax_id_collection' => ['enabled' => true],
                'customer_update' => ['address' => 'auto', 'name' => 'auto'],
            ]);

        return redirect($checkout->url ?? route('workspaces.billing.show', $team));
    }

    public function portal(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manageBilling', $team);

        /** @phpstan-ignore-next-line method.notFound */
        return $team->redirectToCustomerPortal(
            route('workspaces.billing.show', $team)
        );
    }
}
