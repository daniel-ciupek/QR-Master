<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\QrCode;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;

/**
 * Async scan logging — full implementation in Etap 5.
 * Dispatched by PublicRedirectController on every successful redirect.
 *
 * SECURITY: IP is hashed (HMAC-SHA256 + salt) before storage — raw IP never persisted.
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
        public readonly string $userAgent,
        public readonly ?string $referer,
        public readonly string $language,
    ) {}

    public static function fromRequest(QrCode $qrCode, Request $request): self
    {
        $ip = $request->ip() ?? '';
        $salt = config('app.ip_hash_salt', config('app.key'));

        return new self(
            qrCodeId: $qrCode->id,
            ipHash: hash_hmac('sha256', $ip, (string) $salt),
            userAgent: $request->userAgent() ?? '',
            referer: $request->header('referer'),
            language: substr($request->header('Accept-Language', 'en'), 0, 10),
        );
    }

    public function handle(): void
    {
        // Full geo-IP + UA parsing implemented in Etap 5.
    }
}
