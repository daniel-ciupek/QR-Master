<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\QrCode;
use App\Models\ScanLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $plan = $user->effectivePlan();

        $qrCodeIds = QrCode::forUser($user->id)->pluck('id');
        $qrCount = $qrCodeIds->count();

        $activeQrCount = QrCode::forUser($user->id)->where('is_active', true)->count();

        $expiringCount = QrCode::forUser($user->id)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>=', now())
            ->where('expires_at', '<=', now()->addDays(7))
            ->count();

        $scansThisMonth = $qrCodeIds->isEmpty() ? 0 : ScanLog::whereIn('qr_code_id', $qrCodeIds)
            ->where('scanned_at', '>=', now()->startOfMonth())
            ->count();

        $scansLastMonth = $qrCodeIds->isEmpty() ? 0 : ScanLog::whereIn('qr_code_id', $qrCodeIds)
            ->where('scanned_at', '>=', now()->subMonth()->startOfMonth())
            ->where('scanned_at', '<', now()->startOfMonth())
            ->count();

        $scanTimeline = $qrCodeIds->isEmpty() ? [] : ScanLog::whereIn('qr_code_id', $qrCodeIds)
            ->where('scanned_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->select(DB::raw('DATE(scanned_at) as date'), DB::raw('count(*) as count'))
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->map(fn ($row) => (int) ($row->getAttributes()['count'] ?? 0));

        // Fill missing days with zero
        $timeline = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $timeline[] = ['date' => $date, 'count' => $scanTimeline[$date] ?? 0];
        }

        $recentQrCodes = QrCode::forUser($user->id)
            ->latest()
            ->limit(5)
            ->get(['id', 'title', 'type', 'short_hash', 'scan_count', 'is_active', 'created_at', 'expires_at']);

        $topQrCodes = QrCode::forUser($user->id)
            ->orderByDesc('scan_count')
            ->limit(5)
            ->get(['id', 'title', 'type', 'short_hash', 'scan_count', 'is_active']);

        $maxQr = $plan->maxDynamicQrCodes();
        $maxScans = $plan->maxScansPerMonth();

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalQr' => $qrCount,
                'activeQr' => $activeQrCount,
                'expiringQr' => $expiringCount,
                'scansThisMonth' => $scansThisMonth,
                'scansLastMonth' => $scansLastMonth,
                'scansTrend' => $scansLastMonth > 0
                    ? round((($scansThisMonth - $scansLastMonth) / $scansLastMonth) * 100, 1)
                    : ($scansThisMonth > 0 ? 100.0 : 0.0),
            ],
            'planUsage' => [
                'plan' => $plan->value,
                'planName' => $plan->label(),
                'qrUsed' => $qrCount,
                'qrMax' => $maxQr,
                'scansUsed' => $scansThisMonth,
                'scansMax' => $maxScans,
            ],
            'scanTimeline' => $timeline,
            'recentQrCodes' => $recentQrCodes,
            'topQrCodes' => $topQrCodes,
        ]);
    }
}
