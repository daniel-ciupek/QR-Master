<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\CustomDomain;
use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class HandleCustomDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        // Use server SERVER_NAME to avoid X-Forwarded-Host spoofing
        $serverName = $request->server('SERVER_NAME');
        $raw = is_string($serverName) ? $serverName : $request->getHost();
        // Strip port, lowercase
        $host = strtolower(explode(':', $raw)[0]);

        $parsed = parse_url((string) config('app.url'), PHP_URL_HOST);
        $appHost = strtolower(is_string($parsed) ? $parsed : '');

        // Exact match for app host — strict, no subdomain wildcard
        if ($host === $appHost) {
            return $next($request);
        }

        // Reject if host contains the app domain as a suffix (e.g. attacker.qr-master.app.evil.com)
        // Only allow verified custom domains from DB
        $domain = CustomDomain::where('domain', $host)
            ->where('status', 'verified')
            ->first();

        if ($domain === null) {
            abort(404);
        }

        $request->attributes->set('custom_domain', $domain);
        $request->attributes->set('custom_domain_user_id', $domain->user_id);

        if ($domain->team_id !== null) {
            $team = Team::find($domain->team_id);

            if ($team !== null) {
                app()->instance('current.team', $team);
                $request->attributes->set('current_team', $team);
            }
        }

        return $next($request);
    }
}
