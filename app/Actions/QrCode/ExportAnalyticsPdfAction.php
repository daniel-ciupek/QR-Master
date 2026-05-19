<?php

declare(strict_types=1);

namespace App\Actions\QrCode;

use App\Models\QrCode;
use App\Models\ScanLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;
use Illuminate\Support\Facades\DB;

final class ExportAnalyticsPdfAction
{
    public function handle(QrCode $qrCode): DomPDF
    {
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
            ->limit(10)
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
            ->limit(20)
            ->get(['scanned_at', 'country', 'city', 'device_type', 'os', 'browser']);

        $maxDailyCount = $dailyScans->max('count') ?: 1;

        return Pdf::loadView('pdf.analytics-report', [
            'qrCode' => $qrCode,
            'stats' => $stats,
            'topCountries' => $topCountries,
            'deviceBreakdown' => $deviceBreakdown,
            'topBrowsers' => $topBrowsers,
            'dailyScans' => $dailyScans,
            'recentScans' => $recentScans,
            'maxDailyCount' => $maxDailyCount,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');
    }
}
