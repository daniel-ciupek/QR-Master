<?php

declare(strict_types=1);

namespace App\Actions\QrCode;

use App\Models\QrCode;
use App\Models\User;

final class BulkToggleActiveQrCodesAction
{
    /** @param array<int> $ids */
    public function handle(User $user, array $ids, bool $isActive): int
    {
        return QrCode::forUser($user->id)
            ->whereIn('id', $ids)
            ->update(['is_active' => $isActive]);
    }
}
