<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrCode;

use App\Http\Controllers\Controller;
use App\Http\Requests\QrCode\ProcessCsvImportRequest;
use App\Http\Requests\QrCode\UploadCsvRequest;
use App\Jobs\ProcessCsvImportRowJob;
use App\Models\User;
use Illuminate\Bus\Batch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class QrCodeImportController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('QrCode/Import');
    }

    public function upload(UploadCsvRequest $request): JsonResponse
    {
        $file = $request->file('csv_file');
        $content = $file->getContent();

        $lines = preg_split('/\r?\n/', trim($content)) ?: [];
        $lines = array_values(array_filter($lines, fn ($l) => trim($l) !== ''));

        if (count($lines) < 2) {
            return response()->json(['message' => 'CSV must contain a header row and at least one data row.'], 422);
        }

        $headers = array_map(
            fn (?string $h) => trim((string) $h),
            str_getcsv(array_shift($lines)),
        );

        $previewRows = [];
        foreach (array_slice($lines, 0, 5) as $line) {
            $row = str_getcsv($line);
            if (count($row) === count($headers)) {
                $previewRows[] = array_combine($headers, $row);
            }
        }

        $sessionKey = Str::uuid()->toString();
        Storage::disk('local')->put("csv-imports/{$sessionKey}.csv", $content);

        return response()->json([
            'session_key' => $sessionKey,
            'headers' => $headers,
            'preview_rows' => $previewRows,
            'total_rows' => count($lines),
        ]);
    }

    public function process(ProcessCsvImportRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $sessionKey = $validated['session_key'];

        $path = "csv-imports/{$sessionKey}.csv";

        if (! Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'CSV file expired or not found. Please upload again.'], 422);
        }

        $content = Storage::disk('local')->get($path) ?? '';
        $lines = preg_split('/\r?\n/', trim($content)) ?: [];
        $lines = array_values(array_filter($lines, fn ($l) => trim($l) !== ''));

        if (count($lines) < 2) {
            return response()->json(['message' => 'No data rows found in CSV.'], 422);
        }

        $headers = array_map(
            fn (?string $h) => trim((string) $h),
            str_getcsv(array_shift($lines)),
        );

        $jobs = [];
        foreach ($lines as $line) {
            $values = str_getcsv($line);
            if (count($values) !== count($headers)) {
                continue;
            }
            $row = array_combine($headers, $values);
            $jobs[] = new ProcessCsvImportRowJob(
                $user->id,
                $row,
                $validated['mapping'],
                $validated['default_type'],
            );
        }

        if ($jobs === []) {
            Storage::disk('local')->delete($path);

            return response()->json(['message' => 'No valid rows found in CSV.'], 422);
        }

        $batch = Bus::batch($jobs)
            ->name("csv-import:{$user->id}")
            ->allowFailures()
            ->onQueue('bulk')
            ->finally(function (Batch $b) use ($path): void {
                Storage::disk('local')->delete($path);
            })
            ->dispatch();

        return response()->json([
            'batch_id' => $batch->id,
            'total' => $batch->totalJobs,
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

        if ($batch->name !== "csv-import:{$user->id}") {
            return response()->json(['message' => 'Batch not found.'], 404);
        }

        $status = match (true) {
            $batch->cancelled() => 'cancelled',
            $batch->finished() && $batch->failedJobs > 0 => 'finished_with_errors',
            $batch->finished() => 'finished',
            default => 'processing',
        };

        return response()->json([
            'status' => $status,
            'total' => $batch->totalJobs,
            'processed' => $batch->processedJobs(),
            'failed' => $batch->failedJobs,
            'progress_percent' => $batch->progress(),
        ]);
    }
}
