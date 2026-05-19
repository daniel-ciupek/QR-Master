<?php

declare(strict_types=1);

namespace App\Actions\QrCode;

use App\Data\QrCodeData;
use App\Models\QrCode;
use App\Models\User;
use App\Services\HashGenerator;
use Illuminate\Support\Facades\Hash;

final class CreateQrCodeAction
{
    public function __construct(private readonly HashGenerator $hashGenerator) {}

    public function handle(User $user, QrCodeData $data): QrCode
    {
        return QrCode::create([
            'user_id' => $user->id,
            'type' => $data->type,
            'title' => $data->title,
            'short_hash' => $this->hashGenerator->generate(),
            'destination_url' => $data->destination_url,
            'fallback_url' => $data->fallback_url,
            'settings' => $data->settings,
            'is_active' => $data->is_active,
            'expires_at' => $data->expires_at,
            'activates_at' => $data->activates_at,
            'geo_allowed_countries' => $data->geo_allowed_countries,
            'scan_limit' => $data->scan_limit,
            'password_hash' => $data->password !== null
                ? Hash::make($data->password)
                : null,
        ]);
    }
}
