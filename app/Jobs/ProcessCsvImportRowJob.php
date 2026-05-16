<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\QrCode\CreateQrCodeAction;
use App\Data\QrCodeData;
use App\Enums\QrCodeType;
use App\Events\CsvImportProgress;
use App\Models\User;
use App\Services\QrContent\QrContentBuilderService;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

final class ProcessCsvImportRowJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 1;

    /**
     * @param  array<string, string|null>  $row  CSV row as assoc array (header → value)
     * @param  array<string, string>  $mapping  QR field → CSV column name
     */
    public function __construct(
        public readonly int $userId,
        public readonly array $row,
        public readonly array $mapping,
        public readonly string $defaultType,
    ) {}

    public function handle(CreateQrCodeAction $action, QrContentBuilderService $builder): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $item = $this->mapRow();
        /** @var User $user */
        $user = Cache::remember("csv-user:{$this->userId}", 300, fn () => User::findOrFail($this->userId));
        $type = QrCodeType::from($item['type'] ?? $this->defaultType);
        $destinationUrl = $builder->build($type, $item);

        $data = new QrCodeData(
            title: (string) ($item['title'] ?? 'Imported QR'),
            type: $type,
            destination_url: $destinationUrl !== '' ? $destinationUrl : ($item['destination_url'] ?? null),
            fallback_url: $item['fallback_url'] ?? null,
            settings: [],
            is_active: ($item['is_active'] ?? 'true') !== 'false',
            expires_at: isset($item['expires_at']) && $item['expires_at'] !== ''
                ? Carbon::parse($item['expires_at'])
                : null,
            scan_limit: isset($item['scan_limit']) && $item['scan_limit'] !== ''
                ? (int) $item['scan_limit']
                : null,
        );

        $qrCode = $action->handle($user, $data);

        if ($data->type === QrCodeType::App) {
            $qrCode->destination_url = route('qr.redirect', $qrCode->short_hash);
            $qrCode->save();
        }

        $this->broadcastProgress();
    }

    private function broadcastProgress(): void
    {
        $batch = $this->batch();

        if ($batch === null) {
            return;
        }

        $processed = $batch->processedJobs() + 1;

        // Broadcast on every 10th job or the last one to keep WebSocket traffic low
        if ($processed % 10 === 0 || $processed >= $batch->totalJobs) {
            CsvImportProgress::dispatch(
                $this->userId,
                $batch->id,
                $processed,
                $batch->totalJobs,
                $batch->failedJobs,
            );
        }
    }

    /** @return array<string, string|null> */
    private function mapRow(): array
    {
        $item = [];

        foreach ($this->mapping as $qrField => $csvColumn) {
            $item[$qrField] = $this->row[$csvColumn] ?? null;
        }

        if (empty($item['type'])) {
            $item['type'] = $this->defaultType;
        }

        return $item;
    }
}
