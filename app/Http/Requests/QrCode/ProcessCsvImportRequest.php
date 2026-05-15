<?php

declare(strict_types=1);

namespace App\Http\Requests\QrCode;

use App\Enums\QrCodeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProcessCsvImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'session_key' => ['required', 'string', 'uuid'],
            'mapping' => ['required', 'array', 'min:1'],
            'mapping.*' => ['required', 'string'],
            'default_type' => [
                'required',
                Rule::enum(QrCodeType::class)->except([QrCodeType::Pdf, QrCodeType::BioLink]),
            ],
        ];
    }
}
