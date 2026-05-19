<?php

declare(strict_types=1);

namespace App\Actions\QrCode;

use App\Models\QrCode;

final class ToggleActiveQrCodeAction
{
    public function handle(QrCode $qrCode): void
    {
        $qrCode->update(['is_active' => ! $qrCode->is_active]);
    }
}
