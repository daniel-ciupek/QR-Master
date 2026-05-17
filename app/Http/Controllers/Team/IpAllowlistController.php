<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Rules\ValidIpOrCidr;
use App\Services\AuditLogger;
use App\Support\IpMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class IpAllowlistController extends Controller
{
    public function show(Request $request, Team $team): Response
    {
        Gate::authorize('manageBilling', $team);

        $settings = $team->settings ?? [];
        $allowlist = array_values((array) ($settings['ip_allowlist'] ?? []));
        $clientIp = $request->ip() ?? '';
        $currentIpAllowed = $allowlist === [] || IpMatcher::matches($clientIp, $allowlist);

        return Inertia::render('Workspace/IpAllowlist', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'allowlist' => $allowlist,
            'currentIp' => $clientIp,
            'currentIpAllowed' => $currentIpAllowed,
        ]);
    }

    public function store(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manageBilling', $team);

        $validated = $request->validate([
            'entry' => ['required', 'string', 'max:45', new ValidIpOrCidr],
        ]);

        $entry = trim($validated['entry']);
        $settings = $team->settings ?? [];
        $allowlist = (array) ($settings['ip_allowlist'] ?? []);

        if (! in_array($entry, $allowlist, true)) {
            $allowlist[] = $entry;
            $settings['ip_allowlist'] = array_values($allowlist);
            $team->update(['settings' => $settings]);

            /** @var User $actor */
            $actor = $request->user();
            AuditLogger::log($team, 'ip_allowlist.added', "IP/CIDR added to allowlist: {$entry}.", $actor);
        }

        return back()->with('success', __('workspace.ip_allowlist.added'));
    }

    public function destroy(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manageBilling', $team);

        $entry = $request->validate([
            'entry' => ['required', 'string', 'max:50'],
        ])['entry'];

        $settings = $team->settings ?? [];
        $allowlist = array_values(array_filter(
            (array) ($settings['ip_allowlist'] ?? []),
            fn (string $e) => $e !== $entry,
        ));

        $settings['ip_allowlist'] = $allowlist;
        $team->update(['settings' => $settings]);

        /** @var User $actor */
        $actor = $request->user();
        AuditLogger::log($team, 'ip_allowlist.removed', "IP/CIDR removed from allowlist: {$entry}.", $actor);

        return back()->with('success', __('workspace.ip_allowlist.removed'));
    }

    public function clear(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manageBilling', $team);

        $settings = $team->settings ?? [];
        $settings['ip_allowlist'] = [];
        $team->update(['settings' => $settings]);

        /** @var User $actor */
        $actor = $request->user();
        AuditLogger::log($team, 'ip_allowlist.cleared', 'IP allowlist cleared — all IPs now allowed.', $actor);

        return back()->with('success', __('workspace.ip_allowlist.cleared'));
    }
}
