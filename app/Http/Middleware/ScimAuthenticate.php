<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ScimAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $teamSlug = $request->route('teamSlug');

        if (! is_string($teamSlug)) {
            return $this->unauthorized();
        }

        $team = Team::where('slug', $teamSlug)->first();

        if ($team === null) {
            return $this->unauthorized();
        }

        $settings = $team->settings ?? [];
        $storedHash = $settings['scim_token_hash'] ?? null;

        if (! is_string($storedHash) || $storedHash === '') {
            return $this->unauthorized();
        }

        $bearer = $request->bearerToken();

        if ($bearer === null || ! hash_equals($storedHash, hash('sha256', $bearer))) {
            return $this->unauthorized();
        }

        $request->attributes->set('scim_team', $team);

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return response()->json([
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
            'status' => '401',
            'detail' => 'Unauthorized',
        ], 401)->header('Content-Type', 'application/scim+json');
    }
}
