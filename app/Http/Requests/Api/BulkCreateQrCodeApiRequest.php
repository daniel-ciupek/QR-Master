<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\QrCodeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BulkCreateQrCodeApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tokenCan('qrcodes:write') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:1000'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.type' => [
                'required',
                Rule::enum(QrCodeType::class)->except([QrCodeType::Pdf, QrCodeType::BioLink]),
            ],
            'items.*.destination_url' => ['nullable', 'string', 'max:2000', 'regex:/^https?:\/\/.+/'],
            'items.*.fallback_url' => ['nullable', 'url', 'max:2000', 'regex:/^https?:\/\/.+/'],
            'items.*.is_active' => ['boolean'],
            'items.*.expires_at' => ['nullable', 'date'],
            'items.*.activates_at' => ['nullable', 'date'],
            'items.*.scan_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'items.*.settings' => ['nullable', 'array'],
        ];
    }
}
