<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Team;
use App\Support\IpMatcher;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnforceIpAllowlist
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Team|null $team */
        $team = $request->attributes->get('current_team');

        if ($team === null) {
            return $next($request);
        }

        $settings = $team->settings ?? [];
        $allowlist = $settings['ip_allowlist'] ?? [];

        if (! is_array($allowlist) || $allowlist === []) {
            return $next($request);
        }

        // Never block access to the allowlist management page itself
        if ($request->routeIs('workspaces.ip-allowlist.*')) {
            return $next($request);
        }

        $clientIp = $request->ip() ?? '';

        if (! IpMatcher::matches($clientIp, array_values($allowlist))) {
            if ($request->expectsJson() || $request->is('api/*', 'scim/*')) {
                return response()->json([
                    'message' => 'Access denied: your IP address is not in the workspace allowlist.',
                    'ip' => $clientIp,
                ], 403);
            }

            return response()->view('errors.ip-blocked', [
                'team' => $team,
                'ip' => $clientIp,
            ], 403);
        }

        return $next($request);
    }
}
