<?php

declare(strict_types=1);

namespace App\Actions\QrCode;

use App\Models\QrCode;
use Illuminate\Http\UploadedFile;

final class UploadPdfMenuAction
{
    public function handle(QrCode $qrCode, UploadedFile $file): void
    {
        $qrCode->addMedia($file)
            ->toMediaCollection('pdf_menu');
    }
}
