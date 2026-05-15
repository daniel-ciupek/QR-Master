<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CustomerPortalController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @phpstan-ignore-next-line method.notFound (Billable trait — dynamic method) */
        return $user->redirectToCustomerPortal(
            route('billing.dashboard')
        );
    }
}
