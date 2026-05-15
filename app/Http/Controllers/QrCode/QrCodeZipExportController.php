<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrCode;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Models\User;
use App\Services\QrRendering\QrRenderer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use ZipArchive;

final class QrCodeZipExportController extends Controller
{
    public function __invoke(Request $request, QrRenderer $renderer): StreamedResponse
    {
        $validated = $request->validate([
            'format' => ['nullable', Rule::in(['png', 'svg'])],
            'ids' => ['nullable', 'array', 'max:1000'],
            'ids.*' => ['integer'],
        ]);

        $format = $validated['format'] ?? 'png';

        /** @var User $user */
        $user = $request->user();

        $qrCodes = QrCode::forUser($user->id)
            ->whereNotNull('destination_url')
            ->when(! empty($validated['ids']), fn ($q) => $q->whereIn('id', $validated['ids']))
            ->select(['id', 'title', 'slug', 'short_hash', 'destination_url'])
            ->get();

        $filename = 'qr-codes-'.now()->format('Y-m-d').'.zip';

        return response()->streamDownload(
            function () use ($qrCodes, $format, $renderer): void {
                $tmpFile = tempnam(sys_get_temp_dir(), 'qr_zip_');
                $tmpFile = $tmpFile !== false ? $tmpFile : sys_get_temp_dir().'/qr_zip_'.uniqid();

                $zip = new ZipArchive;
                $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                foreach ($qrCodes as $qrCode) {
                    $data = $qrCode->destination_url ?? '';
                    $safeName = ($qrCode->slug ?: 'qr').'-'.$qrCode->short_hash;

                    if ($data === '') {
                        continue;
                    }

                    try {
                        $content = $format === 'svg'
                            ? $renderer->svg($data, 'M', true)
                            : $renderer->png($data, 'M', 10);

                        $zip->addFromString("{$safeName}.{$format}", $content);
                    } catch (Throwable) {
                        // Skip codes that cannot be rendered (empty destination, etc.)
                    }
                }

                $zip->close();

                $zipContent = file_get_contents($tmpFile);
                if ($zipContent !== false) {
                    echo $zipContent;
                }

                @unlink($tmpFile);
            },
            $filename,
            [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ],
        );
    }
}
