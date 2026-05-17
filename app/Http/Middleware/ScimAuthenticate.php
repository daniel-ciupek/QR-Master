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

        $bearer = $request->bearerToken();

        if ($bearer === null) {
            return $this->unauthorized();
        }

        if (! $this->tokenValid($team, $bearer)) {
            return $this->unauthorized();
        }

        $request->attributes->set('scim_team', $team);

        return $next($request);
    }

    private function tokenValid(Team $team, string $bearer): bool
    {
        $settings = $team->settings ?? [];
        $incomingHash = hash('sha256', $bearer);

        // New format: list of tokens
        $tokens = $settings['scim_tokens'] ?? null;
        if (is_array($tokens) && $tokens !== []) {
            foreach ($tokens as $token) {
                if (is_array($token) && isset($token['hash']) && is_string($token['hash'])) {
                    if (hash_equals($token['hash'], $incomingHash)) {
                        return true;
                    }
                }
            }

            return false;
        }

        // Legacy format: single scim_token_hash
        $legacyHash = $settings['scim_token_hash'] ?? null;
        if (is_string($legacyHash) && $legacyHash !== '') {
            return hash_equals($legacyHash, $incomingHash);
        }

        return false;
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
