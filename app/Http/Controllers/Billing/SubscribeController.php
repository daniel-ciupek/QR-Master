<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Enums\PlanTier;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SubscribeController extends Controller
{
    public function __invoke(Request $request, string $plan): RedirectResponse
    {
        $tier = PlanTier::tryFrom($plan);

        if ($tier === null || $tier === PlanTier::Free || $tier === PlanTier::Enterprise) {
            return redirect()->route('pricing');
        }

        $priceId = config("plans.{$tier->value}.stripe_price_id");

        if (! is_string($priceId) || $priceId === '') {
            return redirect()->route('pricing')->with(
                'error',
                'Plan not configured. Please contact support.'
            );
        }

        /** @var User $user */
        $user = $request->user();

        $checkout = $user
            ->newSubscription('default', $priceId)
            ->allowPromotionCodes()
            ->checkout([
                'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('pricing'),
                'automatic_tax' => ['enabled' => true],
                'tax_id_collection' => ['enabled' => true],
                'customer_update' => ['address' => 'auto', 'name' => 'auto'],
            ]);

        return redirect($checkout->url ?? route('pricing'));
    }
}
