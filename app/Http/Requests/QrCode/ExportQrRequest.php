<?php

declare(strict_types=1);

namespace App\Http\Requests\QrCode;

use Illuminate\Foundation\Http\FormRequest;

final class ExportQrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'data' => ['required', 'string', 'max:900'],
            'ecc' => ['required', 'string', 'in:L,M,Q,H'],
            'format' => ['required', 'string', 'in:png,svg,pdf,eps'],
        ];
    }
}
