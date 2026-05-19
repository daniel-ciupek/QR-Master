<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class CheckBotSuspicion
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing')) {
            return $next($request);
        }

        $hash = $request->route('hash');
        if (! is_string($hash)) {
            return $next($request);
        }

        $sessionKey = 'turnstile_bot_verified_'.hash('sha256', $request->ip() ?? '');

        if ($request->session()->get($sessionKey)) {
            return $next($request);
        }

        $cacheKey = 'qr_bot:'.hash('sha256', ($request->ip() ?? 'unknown').date('YmdHi'));
        Cache::add($cacheKey, 0, 60);
        $hits = (int) Cache::increment($cacheKey);

        $threshold = (int) config('turnstile.bot_scan_threshold', 20);

        if ($hits > $threshold) {
            return $this->redirectToChallenge($request, $hash);
        }

        return $next($request);
    }

    private function redirectToChallenge(Request $request, string $hash): RedirectResponse
    {
        $request->session()->put('bot_challenge_return', $hash);

        return redirect()->route('qr.challenge.show', $hash);
    }
}
