<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\IndexQrCodeEmbeddingJob;
use App\Models\QrCode;

final class QrCodeObserver
{
    public function created(QrCode $qrCode): void
    {
        IndexQrCodeEmbeddingJob::dispatch($qrCode->id);
    }

    public function updated(QrCode $qrCode): void
    {
        if ($qrCode->wasChanged(['title', 'destination_url', 'type'])) {
            IndexQrCodeEmbeddingJob::dispatch($qrCode->id);
        }
    }
}
