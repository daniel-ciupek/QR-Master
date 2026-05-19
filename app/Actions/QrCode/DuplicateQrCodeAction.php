<?php

declare(strict_types=1);

namespace App\Actions\QrCode;

use App\Models\QrCode;
use App\Models\User;
use App\Services\HashGenerator;

final class DuplicateQrCodeAction
{
    public function __construct(private readonly HashGenerator $hashGenerator) {}

    public function handle(User $user, QrCode $original): QrCode
    {
        return QrCode::create([
            'user_id' => $user->id,
            'type' => $original->type,
            'title' => 'Kopia: '.$original->title,
            'short_hash' => $this->hashGenerator->generate(),
            'destination_url' => $original->destination_url,
            'settings' => $original->settings,
            'is_active' => false,
            'expires_at' => $original->expires_at,
            'password_hash' => $original->password_hash,
        ]);
    }
}
