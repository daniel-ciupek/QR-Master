<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QrCode;
use App\Models\User;

final class QrCodePolicy
{
    public function viewAnalytics(User $user, QrCode $qrCode): bool
    {
        return $user->id === $qrCode->user_id;
    }

    public function update(User $user, QrCode $qrCode): bool
    {
        return $user->id === $qrCode->user_id;
    }

    public function delete(User $user, QrCode $qrCode): bool
    {
        return $user->id === $qrCode->user_id;
    }

    public function duplicate(User $user, QrCode $qrCode): bool
    {
        return $user->id === $qrCode->user_id;
    }
}
