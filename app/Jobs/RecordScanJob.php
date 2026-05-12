<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\GeoLookupInterface;
use App\Models\QrCode;
use App\Models\ScanLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;

/**
 * Async scan logging — dispatched by PublicRedirectController on every redirect.
 *
 * SECURITY: rawIp is only held in queue payload (Redis, seconds to minutes) for geo
 * lookup — it is NEVER written to the database. Only ip_hash is persisted.
 */
final class RecordScanJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 5;

    public bool $failOnTimeout = true;

    public function __construct(
        public readonly int $qrCodeId,
        public readonly string $ipHash,
        public readonly string $rawIp,
        public readonly string $userAgent,
        public readonly ?string $referer,
        public readonly string $language,
    ) {
        $this->onQueue('scans');
    }

    public static function fromRequest(QrCode $qrCode, Request $request): self
    {
        $ip = $request->ip() ?? '';
        $salt = config('app.ip_hash_salt', config('app.key'));

        return new self(
            qrCodeId: $qrCode->id,
            ipHash: hash_hmac('sha256', $ip, (string) $salt),
            rawIp: $ip,
            userAgent: $request->userAgent() ?? '',
            referer: $request->header('referer'),
            language: substr($request->header('Accept-Language', 'en'), 0, 10),
        );
    }

    public function handle(GeoLookupInterface $geo): void
    {
        $geoData = $geo->lookup($this->rawIp);

        ScanLog::create([
            'qr_code_id' => $this->qrCodeId,
            'ip_hash' => $this->ipHash,
            'referrer' => $this->referer,
            'language' => $this->language,
            'country' => $geoData['country'] ?? null,
            'region' => $geoData['region'] ?? null,
            'city' => $geoData['city'] ?? null,
            'lat' => $geoData['lat'] ?? null,
            'lng' => $geoData['lng'] ?? null,
            // device_type, os, browser: populated in 5.6 (UA parsing)
            'scanned_at' => now(),
        ]);
    }
}
