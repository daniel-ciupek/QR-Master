<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\QrCode;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BulkCreateQrCodeApiRequest;
use App\Jobs\Api\CreateQrCodeBulkItemJob;
use App\Models\User;
use Illuminate\Bus\Batch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;

/**
 * @group Bulk Operations
 *
 * Create up to 1000 QR codes in a single asynchronous batch operation.
 */
final class BulkQrCodeController extends Controller
{
    /**
     * Bulk create QR codes
     *
     * Accepts up to 1000 QR code definitions and processes them asynchronously via a queue batch.
     * Returns a `batch_id` immediately (HTTP 202). Poll the status endpoint to track progress.
     * Requires `qrcodes:write` token ability.
     *
     * @bodyParam items object[] required Array of QR code objects (max 1000).
     * @bodyParam items[].title string required Title of the QR code. Example: Product QR
     * @bodyParam items[].type string required QR type. Example: url
     * @bodyParam items[].destination_url string URL for url type. Example: https://product.example.com
     * @bodyParam items[].is_active boolean Whether active on creation. Example: true
     *
     * @response 202 {"data": {"id": "9d8f1a2b-...", "status": "processing", "total": 100, "processed": 0, "failed": 0, "pending": 100, "progress_percent": 0}}
     * @response 403 {"message": "Missing ability: qrcodes:write"}
     */
    public function store(BulkCreateQrCodeApiRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $items = $request->validated()['items'];

        $jobs = array_map(
            fn (array $item) => new CreateQrCodeBulkItemJob($user->id, $item),
            $items,
        );

        $batch = Bus::batch($jobs)
            ->name("bulk:{$user->id}")
            ->allowFailures()
            ->onQueue('bulk')
            ->dispatch();

        return response()->json([
            'data' => $this->formatBatch($batch),
        ], 202);
    }

    /**
     * Bulk batch status
     *
     * Returns the current status and progress of a bulk creation batch.
     * Poll this endpoint every 2–5 seconds until `status` is `finished` or `finished_with_errors`.
     *
     * @urlParam batchId string required The batch UUID returned by the bulk create endpoint. Example: 9d8f1a2b-3c4d-5e6f-7890-abcdef123456
     *
     * @response 200 {"data": {"id": "9d8f1a2b-...", "status": "processing", "total": 100, "processed": 45, "failed": 2, "pending": 55, "progress_percent": 45, "created_at": "2026-05-15T10:00:00+00:00", "finished_at": null}}
     * @response 404 {"message": "Batch not found."}
     */
    public function status(Request $request, string $batchId): JsonResponse
    {
        $batch = Bus::findBatch($batchId);

        if ($batch === null) {
            return response()->json(['message' => 'Batch not found.'], 404);
        }

        /** @var User $user */
        $user = $request->user();

        if ($batch->name !== "bulk:{$user->id}") {
            return response()->json(['message' => 'Batch not found.'], 404);
        }

        return response()->json([
            'data' => $this->formatBatch($batch),
        ]);
    }

    /** @return array<string, mixed> */
    private function formatBatch(Batch $batch): array
    {
        $status = match (true) {
            $batch->cancelled() => 'cancelled',
            $batch->finished() && $batch->failedJobs > 0 => 'finished_with_errors',
            $batch->finished() => 'finished',
            default => 'processing',
        };

        return [
            'id' => $batch->id,
            'status' => $status,
            'total' => $batch->totalJobs,
            'processed' => $batch->processedJobs(),
            'failed' => $batch->failedJobs,
            'pending' => $batch->pendingJobs,
            'progress_percent' => $batch->progress(),
            'created_at' => $batch->createdAt->toIso8601String(),
            'finished_at' => $batch->finishedAt?->toIso8601String(),
        ];
    }
}
