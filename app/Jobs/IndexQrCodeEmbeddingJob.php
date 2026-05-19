<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\QrCode;
use App\Services\AiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

final class IndexQrCodeEmbeddingJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly int $qrCodeId)
    {
        $this->onQueue('embeddings');
    }

    public function handle(AiService $ai): void
    {
        $provider = (string) config('ai.embedding_provider', 'gemini');
        $apiKey = (string) config("prism.providers.{$provider}.api_key", '');

        if ($apiKey === '') {
            return;
        }

        $qrCode = QrCode::find($this->qrCodeId);

        if ($qrCode === null) {
            return;
        }

        $text = implode(' ', array_filter([
            $qrCode->title,
            $qrCode->type->value,
            $qrCode->destination_url,
        ]));

        if (trim($text) === '') {
            return;
        }

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $embedding = $ai->generateEmbedding($text);

        if ($embedding === []) {
            return;
        }

        $vectorLiteral = '['.implode(',', $embedding).']';
        DB::statement('UPDATE qr_codes SET embedding = ?::vector WHERE id = ?', [$vectorLiteral, $qrCode->id]);
    }
}
