<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Models\ScanLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class GlobalAnalyticsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $range = (int) $request->input('range', 30);
        $range = in_array($range, [7, 30, 90], true) ? $range : 30;

        $since = now()->subDays($range)->startOfDay();
        $sincePrevious = now()->subDays($range * 2)->startOfDay();

        $qrCodeIds = QrCode::forUser($user->id)->pluck('id');

        if ($qrCodeIds->isEmpty()) {
            return Inertia::render('Analytics/Index', [
                'range' => $range,
                'stats' => ['totalScans' => 0, 'uniqueScans' => 0, 'avgPerDay' => 0, 'trend' => 0.0],
                'scanTimeline' => [],
                'topQrCodes' => [],
                'deviceBreakdown' => [],
                'countryBreakdown' => [],
                'browserBreakdown' => [],
            ]);
        }

        $base = ScanLog::whereIn('qr_code_id', $qrCodeIds);

        $totalScans = (clone $base)->where('scanned_at', '>=', $since)->count();
        $uniqueScans = (clone $base)->where('scanned_at', '>=', $since)->distinct('ip_hash')->count('ip_hash');
        $prevScans = (clone $base)->where('scanned_at', '>=', $sincePrevious)->where('scanned_at', '<', $since)->count();
        $trend = $prevScans > 0 ? round((($totalScans - $prevScans) / $prevScans) * 100, 1) : ($totalScans > 0 ? 100.0 : 0.0);
        $avgPerDay = round($totalScans / $range, 1);

        $scanTimeline = (clone $base)
            ->where('scanned_at', '>=', $since)
            ->groupBy('date')
            ->select(DB::raw('DATE(scanned_at) as date'), DB::raw('count(*) as count'))
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->map(fn ($row) => (int) ($row->getAttributes()['count'] ?? 0));

        $timeline = [];
        for ($i = $range - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $timeline[] = ['date' => $date, 'count' => $scanTimeline[$date] ?? 0];
        }

        $topQrCodes = QrCode::forUser($user->id)
            ->whereIn('id', $qrCodeIds)
            ->withCount(['scanLogs as period_scans' => fn ($q) => $q->where('scanned_at', '>=', $since)])
            ->orderByDesc('period_scans')
            ->limit(10)
            ->get(['id', 'title', 'type', 'short_hash', 'scan_count', 'is_active'])
            ->map(function (QrCode $qr) use ($totalScans): array {
                /** @var int $periodScans */
                $periodScans = $qr->getAttribute('period_scans') ?? 0;

                return [
                    'id' => $qr->id,
                    'title' => $qr->title,
                    'type' => $qr->type,
                    'short_hash' => $qr->short_hash,
                    'is_active' => $qr->is_active,
                    'total_scans' => $qr->scan_count,
                    'period_scans' => $periodScans,
                    'percent' => $totalScans > 0 ? round($periodScans / $totalScans * 100, 1) : 0.0,
                ];
            });

        $deviceBreakdown = (clone $base)
            ->where('scanned_at', '>=', $since)
            ->groupBy('device_type')
            ->select(DB::raw("coalesce(device_type, 'unknown') as type"), DB::raw('count(*) as count'))
            ->orderByDesc('count')
            ->get();

        $countryBreakdown = (clone $base)
            ->where('scanned_at', '>=', $since)
            ->whereNotNull('country')
            ->groupBy('country')
            ->select('country', DB::raw('count(*) as count'))
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $browserBreakdown = (clone $base)
            ->where('scanned_at', '>=', $since)
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->select('browser', DB::raw('count(*) as count'))
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return Inertia::render('Analytics/Index', [
            'range' => $range,
            'stats' => [
                'totalScans' => $totalScans,
                'uniqueScans' => $uniqueScans,
                'avgPerDay' => $avgPerDay,
                'trend' => $trend,
            ],
            'scanTimeline' => $timeline,
            'topQrCodes' => $topQrCodes,
            'deviceBreakdown' => $deviceBreakdown,
            'countryBreakdown' => $countryBreakdown,
            'browserBreakdown' => $browserBreakdown,
        ]);
    }
}
