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
use Inertia\Inertia;
use Inertia\Response;

final class DataResidencyController extends Controller
{
    private const REGIONS = [
        'eu' => [
            'name' => 'European Union (EU)',
            'flag' => '🇪🇺',
            'description' => 'Data stored and processed on servers located in Germany (Hetzner Cloud). Compliant with GDPR, Schrems II, and EU data sovereignty requirements.',
            'location' => 'Nuremberg & Helsinki, Germany / Finland',
            'provider' => 'Hetzner Cloud',
            'gdpr' => true,
            'certifications' => ['ISO 27001', 'SOC 2 Type II', 'GDPR compliant'],
        ],
        'us' => [
            'name' => 'United States (US)',
            'flag' => '🇺🇸',
            'description' => 'Data stored and processed on US-based infrastructure. Suitable for US-based organisations not subject to EU data sovereignty requirements.',
            'location' => 'Ashburn, Virginia (planned)',
            'provider' => 'Cloudflare R2 (US East)',
            'gdpr' => false,
            'certifications' => ['SOC 2 Type II', 'ISO 27001'],
        ],
    ];

    public function show(Team $team): Response
    {
        Gate::authorize('manageBilling', $team);

        $settings = $team->settings ?? [];
        $currentRegion = $settings['data_region'] ?? 'eu';
        $confirmedAt = $settings['data_residency_confirmed_at'] ?? null;

        return Inertia::render('Workspace/DataResidency', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'currentRegion' => $currentRegion,
            'confirmedAt' => $confirmedAt,
            'regions' => self::REGIONS,
        ]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manageBilling', $team);

        $validated = $request->validate([
            'region' => ['required', 'in:'.implode(',', array_keys(self::REGIONS))],
        ]);

        $region = $validated['region'];
        $settings = $team->settings ?? [];
        $previousRegion = $settings['data_region'] ?? 'eu';
        $settings['data_region'] = $region;
        $settings['data_residency_confirmed_at'] = now()->toIso8601String();
        $team->update(['settings' => $settings]);

        /** @var User $actor */
        $actor = $request->user();
        AuditLogger::log(
            $team,
            'data_residency.changed',
            "Data residency changed from {$previousRegion} to {$region}.",
            $actor,
            null,
            ['previous' => $previousRegion, 'new' => $region],
        );

        return back()->with('success', __('workspace.data_residency.saved'));
    }
}
