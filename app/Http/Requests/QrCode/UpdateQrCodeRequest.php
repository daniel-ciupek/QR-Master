<?php

declare(strict_types=1);

namespace App\Http\Requests\QrCode;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateQrCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'destination_url' => ['nullable', 'string', 'max:900'],
            'is_active' => ['required', 'boolean'],
            'expires_at' => ['nullable', 'date'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer'],
        ];
    }
}
