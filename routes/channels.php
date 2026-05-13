<?php

declare(strict_types=1);

use App\Models\QrCode;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return $user->id === $id;
});

// Only the owner of the QR code can subscribe to its analytics channel
Broadcast::channel('qr-analytics.{qrCodeId}', function (User $user, int $qrCodeId): bool {
    $qrCode = QrCode::find($qrCodeId);

    return $qrCode !== null && $qrCode->user_id === $user->id;
});
