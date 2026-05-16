<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\CustomDomain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class HandleCustomDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if ($host === $appHost || str_ends_with($host, ".{$appHost}")) {
            return $next($request);
        }

        $domain = CustomDomain::where('domain', $host)
            ->where('status', 'verified')
            ->first();

        if ($domain === null) {
            abort(404, "Domain {$host} is not registered.");
        }

        $request->attributes->set('custom_domain', $domain);
        $request->attributes->set('custom_domain_user_id', $domain->user_id);

        return $next($request);
    }
}
