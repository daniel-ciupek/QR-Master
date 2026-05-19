<?php

declare(strict_types=1);

namespace App\Actions\QrCode;

use App\Models\QrCode;
use App\Models\User;

final class BulkDeleteQrCodesAction
{
    /** @param array<int> $ids */
    public function handle(User $user, array $ids): int
    {
        return QrCode::forUser($user->id)
            ->whereIn('id', $ids)
            ->delete();
    }
}
