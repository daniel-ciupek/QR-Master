<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\UserSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class TrackUserSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (auth()->check() && $request->hasSession()) {
            $sessionId = $request->session()->getId();
            $userId = auth()->id();
            $salt = (string) config('app.ip_hash_salt', config('app.key'));
            $ipHash = hash_hmac('sha256', $request->ip() ?? '', $salt);

            UserSession::updateOrCreate(
                ['session_id' => $sessionId],
                [
                    'user_id' => $userId,
                    'ip_hash' => $ipHash,
                    'user_agent' => $request->userAgent(),
                    'last_active_at' => now(),
                ],
            );

            UserSession::where('user_id', $userId)
                ->where('last_active_at', '<', now()->subMinutes(config('session.lifetime', 120)))
                ->delete();
        }

        return $response;
    }
}
