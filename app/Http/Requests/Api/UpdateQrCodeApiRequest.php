<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateQrCodeApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! ($this->user()?->tokenCan('qrcodes:write') ?? false)) {
            return false;
        }

        /** @var User $user */
        $user = $this->user();
        /** @var QrCode $qrCode */
        $qrCode = $this->route('qrCode');

        return $user->id === $qrCode->user_id;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'destination_url' => ['nullable', 'string', 'max:2000', 'regex:/^https?:\/\/.+/'],
            'fallback_url' => ['nullable', 'url', 'max:2000', 'regex:/^https?:\/\/.+/'],
            'is_active' => ['required', 'boolean'],
            'expires_at' => ['nullable', 'date'],
            'activates_at' => ['nullable', 'date'],
            'geo_allowed_countries' => ['nullable', 'array'],
            'geo_allowed_countries.*' => ['string', 'size:2', 'alpha'],
            'scan_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer'],
        ];
    }
}
