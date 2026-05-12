<?php

declare(strict_types=1);

namespace App\Actions\QrCode;

use App\Models\QrCode;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class UploadQrLogoAction
{
    public function handle(QrCode $qrCode, UploadedFile $file): Media
    {
        return $qrCode
            ->addMedia($file)
            ->toMediaCollection('logo');
    }
}
