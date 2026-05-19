<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class TrackAffiliateRef
{
    public function handle(Request $request, Closure $next): Response
    {
        $ref = $request->query('ref');

        if (is_string($ref) && $ref !== '' && ! $request->session()->has('affiliate_ref')) {
            $request->session()->put('affiliate_ref', strtoupper($ref));
        }

        return $next($request);
    }
}
