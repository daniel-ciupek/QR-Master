<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrCode;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Models\ScanLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class QrCodeAnalyticsController extends Controller
{
    public function __invoke(Request $request, QrCode $qrCode): Response
    {
        Gate::authorize('viewAnalytics', $qrCode);

        $base = ScanLog::where('qr_code_id', $qrCode->id);

        $stats = [
            'total' => (clone $base)->count(),
            'unique' => (clone $base)->distinct('ip_hash')->count('ip_hash'),
            'today' => (clone $base)->whereDate('scanned_at', today())->count(),
            'this_week' => (clone $base)->where('scanned_at', '>=', now()->startOfWeek())->count(),
            'this_month' => (clone $base)->where('scanned_at', '>=', now()->startOfMonth())->count(),
        ];

        $topCountries = (clone $base)
            ->whereNotNull('country')
            ->groupBy('country')
            ->select('country', DB::raw('count(*) as count'))
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $deviceBreakdown = (clone $base)
            ->groupBy('device_type')
            ->select(DB::raw("coalesce(device_type, 'unknown') as type"), DB::raw('count(*) as count'))
            ->orderByDesc('count')
            ->get();

        $topBrowsers = (clone $base)
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->select('browser', DB::raw('count(*) as count'))
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $dailyScans = (clone $base)
            ->where('scanned_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->select(DB::raw('DATE(scanned_at) as date'), DB::raw('count(*) as count'))
            ->orderBy('date')
            ->get();

        $recentScans = (clone $base)
            ->orderByDesc('scanned_at')
            ->limit(10)
            ->get(['scanned_at', 'country', 'city', 'device_type', 'os', 'browser', 'referrer', 'language']);

        $isPgsql = DB::getDriverName() === 'pgsql';
        $hourlyHeatmap = (clone $base)
            ->groupBy('day_of_week', 'hour')
            ->select(
                DB::raw($isPgsql
                    ? 'EXTRACT(DOW FROM scanned_at)::int AS day_of_week'
                    : "CAST(strftime('%w', scanned_at) AS INTEGER) AS day_of_week"),
                DB::raw($isPgsql
                    ? 'EXTRACT(HOUR FROM scanned_at)::int AS hour'
                    : "CAST(strftime('%H', scanned_at) AS INTEGER) AS hour"),
                DB::raw('count(*) AS count'),
            )
            ->get();

        return Inertia::render('QrCode/Analytics', [
            'qrCode' => $qrCode->only('id', 'title', 'short_hash', 'type', 'destination_url', 'is_active', 'created_at'),
            'stats' => $stats,
            'topCountries' => $topCountries,
            'deviceBreakdown' => $deviceBreakdown,
            'topBrowsers' => $topBrowsers,
            'dailyScans' => $dailyScans,
            'recentScans' => $recentScans,
            'hourlyHeatmap' => $hourlyHeatmap,
        ]);
    }
}
