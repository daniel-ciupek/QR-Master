<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WebhookDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Throwable;

final class DeliverWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(
        public readonly int $deliveryId,
    ) {
        $this->onQueue('webhooks');
    }

    /** Exponential backoff: 1 min, 5 min, 15 min, 1 h, 4 h */
    /** @return int[] */
    public function backoff(): array
    {
        return [60, 300, 900, 3600, 14400];
    }

    public function handle(): void
    {
        $delivery = WebhookDelivery::with('webhook')->find($this->deliveryId);

        if ($delivery === null) {
            return;
        }

        $webhook = $delivery->webhook;

        if ($webhook === null || ! $webhook->is_active) {
            $delivery->update(['status' => 'failed', 'response_body' => 'Webhook deactivated.']);

            return;
        }

        $payloadJson = (string) json_encode($delivery->payload);
        $signature = 'sha256='.hash_hmac('sha256', $payloadJson, $webhook->secret);

        $delivery->increment('attempts');
        $delivery->update(['last_attempted_at' => now(), 'status' => 'pending']);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-QR-Master-Signature' => $signature,
                    'X-QR-Master-Event' => $delivery->event,
                    'User-Agent' => 'QR-Master-Webhook/1.0',
                ])
                ->withBody($payloadJson, 'application/json')
                ->post($webhook->url);

            $delivery->update([
                'status' => $response->successful() ? 'success' : 'failed',
                'response_status' => $response->status(),
                'response_body' => mb_substr($response->body(), 0, 1000),
                'delivered_at' => $response->successful() ? now() : null,
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException("HTTP {$response->status()}");
            }
        } catch (Throwable $e) {
            if (! isset($response)) {
                $delivery->update([
                    'status' => 'failed',
                    'response_body' => mb_substr($e->getMessage(), 0, 1000),
                ]);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        WebhookDelivery::where('id', $this->deliveryId)
            ->where('status', '!=', 'success')
            ->update(['status' => 'failed']);
    }
}
