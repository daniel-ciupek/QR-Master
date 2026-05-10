<?php

declare(strict_types=1);

namespace App\Actions\QrCode;

use App\Models\QrCode;

final class DeleteQrCodeAction
{
    public function handle(QrCode $qrCode): void
    {
        $qrCode->delete();
    }
}
