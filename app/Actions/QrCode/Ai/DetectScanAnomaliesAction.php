<?php

declare(strict_types=1);

namespace App\Actions\QrCode\Ai;

use App\Models\QrCode;
use App\Services\AiService;
use Illuminate\Support\Facades\DB;

final class DetectScanAnomaliesAction
{
    public function __construct(private readonly AiService $ai) {}

    /** @return array{is_anomalous: bool, confidence: string, reasons: list<string>, recommendation: string} */
    public function handle(QrCode $qrCode): array
    {
        $lastHour = DB::table('scan_logs')
            ->where('qr_code_id', $qrCode->id)
            ->where('scanned_at', '>=', now()->subHour())
            ->count();

        $last24h = DB::table('scan_logs')
            ->where('qr_code_id', $qrCode->id)
            ->where('scanned_at', '>=', now()->subHours(24))
            ->count();

        $uniqueHashes = DB::table('scan_logs')
            ->where('qr_code_id', $qrCode->id)
            ->where('scanned_at', '>=', now()->subHours(24))
            ->distinct('ip_hash')
            ->count('ip_hash');

        $topDevice = DB::table('scan_logs')
            ->where('qr_code_id', $qrCode->id)
            ->where('scanned_at', '>=', now()->subHours(24))
            ->whereNotNull('device_type')
            ->selectRaw('device_type, COUNT(*) as cnt')
            ->groupBy('device_type')
            ->orderByDesc('cnt')
            ->first();

        $topCountry = DB::table('scan_logs')
            ->where('qr_code_id', $qrCode->id)
            ->where('scanned_at', '>=', now()->subHours(24))
            ->whereNotNull('country')
            ->selectRaw('country, COUNT(*) as cnt')
            ->groupBy('country')
            ->orderByDesc('cnt')
            ->first();

        $topDeviceRatio = ($last24h > 0 && $topDevice !== null)
            ? round((int) $topDevice->cnt / $last24h, 2)
            : 0.0;

        $topCountryRatio = ($last24h > 0 && $topCountry !== null)
            ? round((int) $topCountry->cnt / $last24h, 2)
            : 0.0;

        $avgPerMinute = $lastHour > 0 ? round($lastHour / 60, 2) : 0.0;

        $stats = [
            'scans_last_hour' => $lastHour,
            'scans_last_24h' => $last24h,
            'unique_ip_hashes' => $uniqueHashes,
            'top_device_ratio' => $topDeviceRatio,
            'top_country_ratio' => $topCountryRatio,
            'avg_scans_per_minute' => $avgPerMinute,
        ];

        return $this->ai->detectScanAnomalies($stats, $qrCode->title);
    }
}
