<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class ScimTokenController extends Controller
{
    private const MAX_TOKENS = 5;

    public function show(Team $team): Response
    {
        Gate::authorize('manageBilling', $team);

        $settings = $team->settings ?? [];
        $rawTokens = $settings['scim_tokens'] ?? [];

        // Strip hash before sending to frontend
        $tokens = array_values(array_map(fn (array $t) => [
            'id' => $t['id'],
            'name' => $t['name'] ?? '',
            'created_at' => $t['created_at'] ?? '',
        ], is_array($rawTokens) ? $rawTokens : []));

        return Inertia::render('Workspace/Scim', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'tokens' => $tokens,
            'newToken' => session('scim_new_token'),
            'scimBaseUrl' => url("/scim/v2/{$team->slug}"),
            'maxTokens' => self::MAX_TOKENS,
        ]);
    }

    public function generate(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manageBilling', $team);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:40'],
        ]);

        $settings = $team->settings ?? [];
        $tokens = is_array($settings['scim_tokens'] ?? null) ? $settings['scim_tokens'] : [];

        if (count($tokens) >= self::MAX_TOKENS) {
            return back()->withErrors(['name' => __('workspace.scim.max_tokens_reached')]);
        }

        $plainToken = Str::random(64);

        $tokens[] = [
            'id' => Str::uuid()->toString(),
            'name' => trim((string) ($validated['name'] ?? '')) ?: 'Token #'.(count($tokens) + 1),
            'hash' => hash('sha256', $plainToken),
            'created_at' => now()->toIso8601String(),
        ];

        $settings['scim_tokens'] = $tokens;
        $team->update(['settings' => $settings]);

        /** @var User $actor */
        $actor = $request->user();
        AuditLogger::log($team, 'scim.token.generated', 'SCIM provisioning token generated.', $actor);

        return redirect()->route('workspaces.scim.show', $team)
            ->with('scim_new_token', $plainToken);
    }

    public function revoke(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manageBilling', $team);

        $validated = $request->validate([
            'token_id' => ['required', 'string', 'uuid'],
        ]);

        $settings = $team->settings ?? [];
        $tokens = is_array($settings['scim_tokens'] ?? null) ? $settings['scim_tokens'] : [];

        $settings['scim_tokens'] = array_values(
            array_filter($tokens, fn (array $t) => ($t['id'] ?? '') !== $validated['token_id'])
        );

        $team->update(['settings' => $settings]);

        /** @var User $actor */
        $actor = $request->user();
        AuditLogger::log($team, 'scim.token.revoked', 'SCIM provisioning token revoked.', $actor);

        return back()->with('success', __('workspace.scim.revoked'));
    }
}
