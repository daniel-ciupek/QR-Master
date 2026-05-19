<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\QrCodeType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreQrCodeApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ability check is in the controller (abort_unless tokenCan)
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => [
                'required',
                Rule::enum(QrCodeType::class)->except([QrCodeType::Pdf, QrCodeType::BioLink]),
            ],
            'is_active' => ['boolean'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'activates_at' => ['nullable', 'date'],
            'geo_allowed_countries' => ['nullable', 'array'],
            'geo_allowed_countries.*' => ['string', 'size:2', 'alpha'],
            'scan_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'fallback_url' => ['nullable', 'url', 'max:2000', 'regex:/^https?:\/\/.+/'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer'],

            'destination_url' => ['required_if:type,url', 'nullable', 'string', 'max:2000', 'regex:/^https?:\/\/.+/'],

            'text_content' => ['required_if:type,text', 'nullable', 'string', 'max:2000'],

            'email_address' => ['required_if:type,email', 'nullable', 'email', 'max:255'],
            'email_subject' => ['nullable', 'string', 'max:255'],
            'email_body' => ['nullable', 'string', 'max:900'],

            'phone_number' => ['required_if:type,phone', 'nullable', 'string', 'max:30'],

            'sms_number' => ['required_if:type,sms', 'nullable', 'string', 'max:30'],
            'sms_message' => ['nullable', 'string', 'max:160'],

            'vcard_first_name' => ['required_if:type,vcard', 'nullable', 'string', 'max:100'],
            'vcard_last_name' => ['nullable', 'string', 'max:100'],
            'vcard_company' => ['nullable', 'string', 'max:200'],
            'vcard_job_title' => ['nullable', 'string', 'max:200'],
            'vcard_website' => ['nullable', 'url', 'max:900'],
            'vcard_address' => ['nullable', 'string', 'max:500'],
            'vcard_phone' => ['nullable', 'string', 'max:30'],
            'vcard_email' => ['nullable', 'email', 'max:255'],

            'wifi_ssid' => ['required_if:type,wifi', 'nullable', 'string', 'max:32'],
            'wifi_security' => ['required_if:type,wifi', 'nullable', Rule::in(['open', 'wpa', 'wpa2', 'wpa3', 'wep'])],
            'wifi_password' => ['nullable', 'string', 'max:63'],
            'wifi_hidden' => ['nullable', 'boolean'],

            'geo_lat' => ['required_if:type,geo', 'nullable', 'numeric', 'between:-90,90'],
            'geo_lng' => ['required_if:type,geo', 'nullable', 'numeric', 'between:-180,180'],

            'app_ios_url' => ['nullable', 'url', 'max:2000', 'regex:/^https?:\/\/.+/'],
            'app_android_url' => ['nullable', 'url', 'max:2000', 'regex:/^https?:\/\/.+/'],
            'app_fallback_url' => ['nullable', 'url', 'max:2000', 'regex:/^https?:\/\/.+/'],

            'calendar_title' => ['required_if:type,calendar', 'nullable', 'string', 'max:255'],
            'calendar_start' => ['required_if:type,calendar', 'nullable', 'date'],
            'calendar_end' => ['nullable', 'date', 'after_or_equal:calendar_start'],
            'calendar_description' => ['nullable', 'string', 'max:1000'],
            'calendar_location' => ['nullable', 'string', 'max:500'],
            'calendar_all_day' => ['nullable', 'boolean'],

            'crypto_coin' => ['required_if:type,crypto', 'nullable', 'string', Rule::in(['bitcoin', 'ethereum', 'litecoin', 'dogecoin'])],
            'crypto_address' => ['required_if:type,crypto', 'nullable', 'string', 'min:20', 'max:200'],
            'crypto_amount' => ['nullable', 'numeric', 'min:0'],
            'crypto_label' => ['nullable', 'string', 'max:100'],
            'crypto_message' => ['nullable', 'string', 'max:255'],

            'review_platform' => ['required_if:type,review', 'nullable', Rule::in(['google', 'trustpilot', 'yelp', 'facebook', 'tripadvisor', 'other'])],
            'review_url' => ['required_if:type,review', 'nullable', 'url', 'max:2000', 'regex:/^https?:\/\/.+/'],

            'settings' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if ($this->input('type') === 'app') {
            $validator->after(function (Validator $v): void {
                if (! $this->filled('app_ios_url') && ! $this->filled('app_android_url')) {
                    $v->errors()->add('app_ios_url', 'At least one store URL (iOS or Android) is required.');
                }
            });
        }
    }
}
