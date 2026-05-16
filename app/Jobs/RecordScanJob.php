<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\GeoLookupInterface;
use App\Contracts\UserAgentParserInterface;
use App\Events\QrCodeScanned;
use App\Models\QrCode;
use App\Models\ScanLog;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
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

    public int $tries = 1;

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

    public function handle(GeoLookupInterface $geo, UserAgentParserInterface $uaParser): void
    {
        $geoData = $geo->lookup($this->rawIp);
        $uaData = $uaParser->parse($this->userAgent);

        $scannedAt = now();

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
            'device_type' => $uaData['device_type'],
            'os' => $uaData['os'],
            'browser' => $uaData['browser'],
            'scanned_at' => $scannedAt,
        ]);

        QrCodeScanned::dispatch($this->qrCodeId, $scannedAt->toIso8601String());

        $this->dispatchWebhooks($this->qrCodeId, $scannedAt->toIso8601String(), [
            'country' => $geoData['country'] ?? null,
            'device_type' => $uaData['device_type'],
            'browser' => $uaData['browser'],
            'os' => $uaData['os'],
        ]);
    }

    /**
     * @param  array<string, string|null>  $meta
     */
    private function dispatchWebhooks(int $qrCodeId, string $scannedAt, array $meta): void
    {
        $qrCode = QrCode::find($qrCodeId);

        if ($qrCode === null) {
            return;
        }

        $webhooks = Webhook::where('user_id', $qrCode->user_id)
            ->where('is_active', true)
            ->whereJsonContains('events', 'qr.scanned')
            ->get();

        if ($webhooks->isEmpty()) {
            return;
        }

        $now = now();
        $payload = json_encode([
            'event' => 'qr.scanned',
            'timestamp' => $scannedAt,
            'data' => array_merge(['qr_code_id' => $qrCodeId, 'scanned_at' => $scannedAt], $meta),
        ]);

        $deliveriesData = $webhooks->map(fn (Webhook $webhook) => [
            'webhook_id' => $webhook->id,
            'event' => 'qr.scanned',
            'payload' => $payload,
            'status' => 'pending',
            'attempts' => 0,
            'created_at' => $now,
        ])->all();

        WebhookDelivery::insert($deliveriesData);

        $deliveryIds = WebhookDelivery::whereIn('webhook_id', $webhooks->pluck('id'))
            ->where('created_at', '>=', $now)
            ->pluck('id');

        foreach ($deliveryIds as $deliveryId) {
            DeliverWebhookJob::dispatch($deliveryId);
        }
    }
}
