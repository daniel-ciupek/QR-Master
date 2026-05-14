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

            // URL — required when type=url, scheme restricted to http/https
            'destination_url' => [
                'required_if:type,url',
                'nullable',
                'string',
                'max:2000',
                'regex:/^https?:\/\/.+/',
            ],

            // Text
            'text_content' => ['required_if:type,text', 'nullable', 'string', 'max:2000'],

            // Email
            'email_address' => ['required_if:type,email', 'nullable', 'email', 'max:255'],
            'email_subject' => ['nullable', 'string', 'max:255'],
            'email_body' => ['nullable', 'string', 'max:900'],

            // Phone
            'phone_number' => ['required_if:type,phone', 'nullable', 'string', 'max:30'],

            // SMS
            'sms_number' => ['required_if:type,sms', 'nullable', 'string', 'max:30'],
            'sms_message' => ['nullable', 'string', 'max:160'],

            // vCard — first name required, phone/email go to encrypted columns
            'vcard_first_name' => ['required_if:type,vcard', 'nullable', 'string', 'max:100'],
            'vcard_last_name' => ['nullable', 'string', 'max:100'],
            'vcard_company' => ['nullable', 'string', 'max:200'],
            'vcard_job_title' => ['nullable', 'string', 'max:200'],
            'vcard_website' => ['nullable', 'url', 'max:900'],
            'vcard_address' => ['nullable', 'string', 'max:500'],
            'vcard_photo_url' => ['nullable', 'url', 'max:900'],
            'vcard_phone' => ['nullable', 'string', 'max:30'],
            'vcard_email' => ['nullable', 'email', 'max:255'],

            // WiFi — password stored encrypted, SSID required
            'wifi_ssid' => ['required_if:type,wifi', 'nullable', 'string', 'max:32'],
            'wifi_security' => ['required_if:type,wifi', 'nullable', Rule::in(['open', 'wpa', 'wpa2', 'wpa3', 'wep'])],
            'wifi_password' => ['nullable', 'string', 'max:63'],
            'wifi_hidden' => ['nullable', 'boolean'],

            // Geo — both coordinates required together
            'geo_lat' => ['required_if:type,geo', 'nullable', 'numeric', 'between:-90,90'],
            'geo_lng' => ['required_if:type,geo', 'nullable', 'numeric', 'between:-180,180'],

            // Visual settings (pass-through, no deep validation)
            'settings' => ['nullable', 'array'],
        ];
    }
}
