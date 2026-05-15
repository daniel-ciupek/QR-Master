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

final class BulkQrCodeController extends Controller
{
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
