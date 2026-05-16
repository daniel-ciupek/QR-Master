<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePlanFeature
{
    /** @var array<string, string> */
    private const FEATURE_MAP = [
        'analytics' => 'canUseAnalytics',
        'ab-test' => 'canUseAbTest',
        'smart-redirect' => 'canUseSmartRedirect',
        'api' => 'canUseApi',
        'custom-branding' => 'canUseCustomBranding',
        'webhooks' => 'canUseWebhooks',
        'bulk-import' => 'canUseBulkImport',
    ];

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $method = self::FEATURE_MAP[$feature] ?? null;

        if ($method === null) {
            return $next($request);
        }

        if (! $user->plan_tier->{$method}()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Upgrade your plan to access this feature.'], 403);
            }

            return redirect()->route('pricing')->with('upgrade_required', $feature);
        }

        return $next($request);
    }
}
