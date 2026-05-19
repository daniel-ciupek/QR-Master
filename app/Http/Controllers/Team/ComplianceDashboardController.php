<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Models\ScanLog;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class ComplianceDashboardController extends Controller
{
    public function show(Team $team): Response
    {
        Gate::authorize('update', $team);

        $qrIds = QrCode::where('team_id', $team->id)->pluck('id');
        $totalScans = ScanLog::whereIn('qr_code_id', $qrIds)->count();
        $geoAnonymized = ScanLog::whereIn('qr_code_id', $qrIds)->whereNull('country')->count();
        $geoRetained = $totalScans - $geoAnonymized;
        $dueSoon = ScanLog::whereIn('qr_code_id', $qrIds)
            ->whereNotNull('country')
            ->where('scanned_at', '<', now()->subDays(80))
            ->count();

        $compliance = (array) ($team->settings['compliance'] ?? []);
        $dpa = $team->settings['dpa'] ?? null;

        return Inertia::render('Workspace/Compliance', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'stats' => [
                'qrCodes' => $qrIds->count(),
                'members' => $team->members()->count(),
                'totalScans' => $totalScans,
                'geoAnonymized' => $geoAnonymized,
                'geoRetained' => $geoRetained,
                'dueSoon' => $dueSoon,
            ],
            'status' => [
                'dpaGenerated' => $dpa !== null,
                'dpaDate' => is_array($dpa) ? ($dpa['agreement_date'] ?? null) : null,
                'retentionActive' => true,
                'retentionDays' => 90,
                'exportAvailable' => true,
                'lastAuditAt' => $compliance['last_audit_at'] ?? null,
                'auditNotes' => $compliance['audit_notes'] ?? null,
                'dataRegion' => $team->settings['data_region'] ?? 'eu',
                'dataResidencyConfirmedAt' => $team->settings['data_residency_confirmed_at'] ?? null,
            ],
            'processors' => $this->subProcessors(),
            'dataCategories' => $this->dataCategories(),
        ]);
    }

    public function markAudited(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('update', $team);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $settings = $team->settings ?? [];
        $settings['compliance']['last_audit_at'] = now()->toDateString();
        $settings['compliance']['audit_notes'] = $validated['notes'] ?? null;
        $team->update(['settings' => $settings]);

        return back()->with('success', __('workspace.compliance.audit_saved'));
    }

    /** @return list<array{name: string, country: string, purpose: string, transfer: string}> */
    private function subProcessors(): array
    {
        return [
            ['name' => 'Hetzner Cloud', 'country' => 'Germany (EU)', 'purpose' => 'Server infrastructure', 'transfer' => 'No transfer'],
            ['name' => 'Cloudflare R2', 'country' => 'USA', 'purpose' => 'Media storage (logos, QR images)', 'transfer' => 'SCCs'],
            ['name' => 'Stripe', 'country' => 'USA', 'purpose' => 'Payment processing', 'transfer' => 'SCCs'],
            ['name' => 'Sentry', 'country' => 'USA', 'purpose' => 'Error monitoring (PII filtered)', 'transfer' => 'SCCs'],
            ['name' => 'Typesense', 'country' => 'Self-hosted / EU', 'purpose' => 'Search index', 'transfer' => 'No transfer'],
        ];
    }

    /** @return list<array{category: string, data: string, purpose: string, retention: string, legal_basis: string}> */
    private function dataCategories(): array
    {
        return [
            [
                'category' => 'Scan analytics',
                'data' => 'IP hash (SHA-256), device type, browser, OS, country, city',
                'purpose' => 'QR code performance analytics',
                'retention' => 'Geo: 90 days, then anonymized. Hashed IP: indefinite.',
                'legal_basis' => 'Legitimate interests (Art. 6(1)(f))',
            ],
            [
                'category' => 'Account data',
                'data' => 'Name, email address, password hash (Argon2id)',
                'purpose' => 'Account management and authentication',
                'retention' => 'Until account deletion request',
                'legal_basis' => 'Contract performance (Art. 6(1)(b))',
            ],
            [
                'category' => 'Billing data',
                'data' => 'Stripe customer ID, last 4 card digits, payment method type',
                'purpose' => 'Subscription billing',
                'retention' => 'Stripe retains per their policy',
                'legal_basis' => 'Contract performance (Art. 6(1)(b))',
            ],
            [
                'category' => 'QR Code content',
                'data' => 'Destination URLs, vCard data (encrypted), WiFi passwords (encrypted)',
                'purpose' => 'QR code functionality',
                'retention' => '30 days after deletion, then purged',
                'legal_basis' => 'Contract performance (Art. 6(1)(b))',
            ],
        ];
    }
}
