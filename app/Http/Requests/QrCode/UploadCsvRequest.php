<?php

declare(strict_types=1);

namespace App\Http\Requests\QrCode;

use Illuminate\Foundation\Http\FormRequest;

final class UploadCsvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'csv_file' => ['required', 'file', 'mimetypes:text/plain,text/csv,application/csv', 'max:10240'],
        ];
    }
}
