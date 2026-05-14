<?php

declare(strict_types=1);

namespace App\Http\Requests\QrCode;

use Illuminate\Foundation\Http\FormRequest;

final class CompareAnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:2', 'max:5'],
            'ids.*' => ['required', 'integer', 'min:1'],
        ];
    }
}
