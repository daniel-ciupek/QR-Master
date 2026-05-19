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

// CSV import progress — only the user who started the import
Broadcast::channel('csv-import.{userId}', function (User $user, int $userId): bool {
    return $user->id === $userId;
});

// Presence channel for real-time collaboration on QR code editing
// Returns user info for presence list — owner and team members with access
Broadcast::channel('qr-edit.{qrCodeId}', function (User $user, int $qrCodeId): array|false {
    $qrCode = QrCode::find($qrCodeId);

    if ($qrCode === null || $qrCode->user_id !== $user->id) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'initials' => mb_strtoupper(mb_substr($user->name, 0, 1)),
    ];
});
