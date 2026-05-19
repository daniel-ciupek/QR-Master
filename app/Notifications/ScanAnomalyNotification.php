<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class ScanAnomalyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $qrCodeId,
        public readonly string $qrCodeTitle,
        public readonly string $anomalyType,
        public readonly float $confidence,
        public readonly string $recommendation,
    ) {
        $this->onQueue('notifications');
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'scan_anomaly',
            'qr_code_id' => $this->qrCodeId,
            'qr_code_title' => $this->qrCodeTitle,
            'anomaly_type' => $this->anomalyType,
            'confidence' => $this->confidence,
            'recommendation' => $this->recommendation,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toBroadcast(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
