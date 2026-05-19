<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CsvImportProgress implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $batchId,
        public readonly int $processed,
        public readonly int $total,
        public readonly int $failed,
    ) {}

    /** @return Channel[] */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("csv-import.{$this->userId}")];
    }

    public function broadcastAs(): string
    {
        return 'progress';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'batch_id' => $this->batchId,
            'processed' => $this->processed,
            'total' => $this->total,
            'failed' => $this->failed,
            'progress_percent' => $this->total > 0
                ? (int) round(($this->processed / $this->total) * 100)
                : 0,
        ];
    }
}
