<?php

declare(strict_types=1);

namespace App\Actions\QrCode;

use App\Data\QrCodeData;
use App\Models\QrCode;
use Illuminate\Support\Facades\Hash;

final class UpdateQrCodeAction
{
    public function handle(QrCode $qrCode, QrCodeData $data): QrCode
    {
        $attributes = [
            'type' => $data->type,
            'title' => $data->title,
            'destination_url' => $data->destination_url,
            'settings' => $data->settings,
            'is_active' => $data->is_active,
            'expires_at' => $data->expires_at,
            'activates_at' => $data->activates_at,
            'geo_allowed_countries' => $data->geo_allowed_countries,
        ];

        if ($data->password !== null) {
            $attributes['password_hash'] = Hash::make($data->password);
        }

        $qrCode->update($attributes);

        return $qrCode->refresh();
    }
}
