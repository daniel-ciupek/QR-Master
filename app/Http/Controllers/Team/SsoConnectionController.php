<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\SsoConnection;
use App\Models\Team;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class SsoConnectionController extends Controller
{
    private const PROVIDERS = ['google', 'azure', 'okta'];

    public function show(Team $team): Response
    {
        Gate::authorize('manageBilling', $team);

        $connection = SsoConnection::where('team_id', $team->id)->first();

        return Inertia::render('Workspace/Sso', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'providers' => self::PROVIDERS,
            'connection' => $connection ? [
                'id' => $connection->id,
                'provider' => $connection->provider,
                'email_domain' => $connection->email_domain,
                'has_client_id' => $connection->client_id !== '',
                'tenant_id' => $connection->tenant_id,
                'okta_domain' => $connection->okta_domain,
                'is_active' => $connection->is_active,
                'auto_provision' => $connection->auto_provision,
            ] : null,
        ]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manageBilling', $team);

        $existing = SsoConnection::where('team_id', $team->id)->first();

        $validated = $request->validate([
            'provider' => ['required', 'in:'.implode(',', self::PROVIDERS)],
            'email_domain' => ['required', 'string', 'max:253', 'regex:/^[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?(\.[a-z]{2,})+$/i'],
            'client_id' => [$existing ? 'nullable' : 'required', 'string', 'max:500'],
            'client_secret' => [$existing ? 'nullable' : 'required', 'string', 'max:500'],
            'tenant_id' => ['nullable', 'string', 'max:253'],
            'okta_domain' => ['nullable', 'string', 'max:253'],
            'is_active' => ['boolean'],
            'auto_provision' => ['boolean'],
        ]);

        $payload = array_filter($validated, fn ($v) => $v !== null);

        SsoConnection::updateOrCreate(
            ['team_id' => $team->id],
            [...$payload, 'team_id' => $team->id],
        );

        /** @var User $actor */
        $actor = $request->user();
        AuditLogger::log($team, 'sso.configured', "SSO configured with provider {$validated['provider']} for domain {$validated['email_domain']}.", $actor);

        return back()->with('success', __('workspace.sso.saved'));
    }

    public function destroy(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manageBilling', $team);

        SsoConnection::where('team_id', $team->id)->delete();

        /** @var User $actor */
        $actor = $request->user();
        AuditLogger::log($team, 'sso.deleted', 'SSO connection removed.', $actor);

        return back()->with('success', __('workspace.sso.deleted'));
    }
}
