<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wymusza 2FA dla użytkowników Pro/Business.
 * TODO (Etap 8): aktywować po implementacji planów subskrypcji.
 */
final class EnsureTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        // Etap 8: $this->planRequiresTwoFactor($user) && ! $user->hasEnabledTwoFactorAuthentication()
        // Na razie 2FA jest opcjonalne dla wszystkich planów.

        return $next($request);
    }
}
