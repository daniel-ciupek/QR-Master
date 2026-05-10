<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (
            $user !== null
            && $user->hasVerifiedEmail()
            && $user->onboarding_completed_at === null
            && ! $request->is('onboarding', 'onboarding/*')
            && ! $request->is('logout', 'locale')
        ) {
            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}
