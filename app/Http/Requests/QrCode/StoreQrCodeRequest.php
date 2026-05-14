<?php

declare(strict_types=1);

namespace App\Http\Requests\QrCode;

use App\Enums\QrCodeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreQrCodeRequest extends FormRequest
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
            'type' => ['required', Rule::enum(QrCodeType::class)],
            'is_active' => ['boolean'],
            'expires_at' => ['nullable', 'date', 'after:today'],

            // URL
            'destination_url' => ['nullable', 'string', 'max:900'],

            // Text
            'text_content' => ['nullable', 'string', 'max:900'],

            // Email
            'email_address' => ['nullable', 'email', 'max:255'],
            'email_subject' => ['nullable', 'string', 'max:255'],
            'email_body' => ['nullable', 'string', 'max:900'],

            // Phone
            'phone_number' => ['nullable', 'string', 'max:30'],

            // SMS
            'sms_number' => ['nullable', 'string', 'max:30'],
            'sms_message' => ['nullable', 'string', 'max:160'],

            // vCard — non-sensitive
            'vcard_first_name' => ['nullable', 'string', 'max:100'],
            'vcard_last_name' => ['nullable', 'string', 'max:100'],
            'vcard_company' => ['nullable', 'string', 'max:200'],
            'vcard_job_title' => ['nullable', 'string', 'max:200'],
            'vcard_website' => ['nullable', 'url', 'max:900'],
            'vcard_address' => ['nullable', 'string', 'max:500'],
            'vcard_photo_url' => ['nullable', 'url', 'max:900'],
            // vCard — sensitive (encrypted columns)
            'vcard_phone' => ['nullable', 'string', 'max:30'],
            'vcard_email' => ['nullable', 'email', 'max:255'],

            // Geo
            'geo_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'geo_lng' => ['nullable', 'numeric', 'between:-180,180'],

            // Visual settings (pass-through, no deep validation)
            'settings' => ['nullable', 'array'],
        ];
    }
}
