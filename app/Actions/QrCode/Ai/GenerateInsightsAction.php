<?php

declare(strict_types=1);

namespace App\Actions\QrCode\Ai;

use App\Models\QrCode;
use App\Services\AiService;
use Illuminate\Support\Facades\DB;

final class GenerateInsightsAction
{
    public function __construct(private readonly AiService $ai) {}

    /** @return array{insight: string} */
    public function handle(QrCode $qrCode): array
    {
        $topCountries = DB::table('scan_logs')
            ->where('qr_code_id', $qrCode->id)
            ->whereNotNull('country')
            ->selectRaw('country, COUNT(*) as count')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(3)
            ->get()
            ->map(fn ($r) => ['country' => (string) $r->country, 'count' => (int) $r->count])
            ->all();

        $deviceBreakdown = DB::table('scan_logs')
            ->where('qr_code_id', $qrCode->id)
            ->whereNotNull('device_type')
            ->selectRaw('device_type as type, COUNT(*) as count')
            ->groupBy('device_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['type' => (string) $r->type, 'count' => (int) $r->count])
            ->all();

        $stats = [
            'total_scans' => $qrCode->scan_count,
            'unique_countries' => DB::table('scan_logs')
                ->where('qr_code_id', $qrCode->id)
                ->whereNotNull('country')
                ->distinct('country')
                ->count('country'),
            'scans_last_7_days' => DB::table('scan_logs')
                ->where('qr_code_id', $qrCode->id)
                ->where('scanned_at', '>=', now()->subDays(7))
                ->count(),
            'scans_last_30_days' => DB::table('scan_logs')
                ->where('qr_code_id', $qrCode->id)
                ->where('scanned_at', '>=', now()->subDays(30))
                ->count(),
            'top_countries' => $topCountries,
            'device_breakdown' => $deviceBreakdown,
        ];

        return $this->ai->generatePerformanceInsights($stats, $qrCode->title);
    }
}
