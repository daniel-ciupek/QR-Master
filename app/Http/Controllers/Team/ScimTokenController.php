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
    public function show(Team $team): Response
    {
        Gate::authorize('manageBilling', $team);

        $settings = $team->settings ?? [];
        $hasToken = isset($settings['scim_token_hash']) && $settings['scim_token_hash'] !== '';

        return Inertia::render('Workspace/Scim', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'hasToken' => $hasToken,
            'newToken' => session('scim_new_token'),
            'scimBaseUrl' => url("/scim/v2/{$team->slug}"),
        ]);
    }

    public function generate(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manageBilling', $team);

        $token = Str::random(64);
        $hash = hash('sha256', $token);

        $settings = $team->settings ?? [];
        $settings['scim_token_hash'] = $hash;
        $team->update(['settings' => $settings]);

        /** @var User $actor */
        $actor = $request->user();
        AuditLogger::log($team, 'scim.token.generated', 'SCIM provisioning token generated.', $actor);

        return redirect()->route('workspaces.scim.show', $team)
            ->with('scim_new_token', $token);
    }

    public function revoke(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manageBilling', $team);

        $settings = $team->settings ?? [];
        unset($settings['scim_token_hash']);
        $team->update(['settings' => $settings]);

        /** @var User $actor */
        $actor = $request->user();
        AuditLogger::log($team, 'scim.token.revoked', 'SCIM provisioning token revoked.', $actor);

        return redirect()->route('workspaces.scim.show', $team)
            ->with('success', __('workspace.scim.revoked'));
    }
}
