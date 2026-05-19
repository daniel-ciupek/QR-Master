<?php

declare(strict_types=1);

namespace App\Actions\AbTest;

use App\Models\AbTest;
use App\Models\QrCode;

final class CreateAbTestAction
{
    public function handle(QrCode $qrCode): AbTest
    {
        return AbTest::create([
            'qr_code_id' => $qrCode->id,
            'is_active' => true,
            'auto_select_winner' => false,
            'auto_select_threshold' => 1000,
        ]);
    }
}
