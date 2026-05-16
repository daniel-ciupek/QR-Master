<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

            'text_content' => ['nullable', 'string', 'max:2000'],

            'email_address' => ['nullable', 'email', 'max:255'],
            'email_subject' => ['nullable', 'string', 'max:255'],
            'email_body' => ['nullable', 'string', 'max:900'],

            'phone_number' => ['nullable', 'string', 'max:30'],

            'sms_number' => ['nullable', 'string', 'max:30'],
            'sms_message' => ['nullable', 'string', 'max:160'],

            'vcard_first_name' => ['nullable', 'string', 'max:100'],
            'vcard_last_name' => ['nullable', 'string', 'max:100'],
            'vcard_company' => ['nullable', 'string', 'max:200'],
            'vcard_job_title' => ['nullable', 'string', 'max:200'],
            'vcard_website' => ['nullable', 'url', 'max:900'],
            'vcard_address' => ['nullable', 'string', 'max:500'],
            'vcard_phone' => ['nullable', 'string', 'max:30'],
            'vcard_email' => ['nullable', 'email', 'max:255'],

            'wifi_ssid' => ['nullable', 'string', 'max:32'],
            'wifi_security' => ['nullable', Rule::in(['open', 'wpa', 'wpa2', 'wpa3', 'wep'])],
            'wifi_password' => ['nullable', 'string', 'max:63'],
            'wifi_hidden' => ['nullable', 'boolean'],

            'geo_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'geo_lng' => ['nullable', 'numeric', 'between:-180,180'],

            'app_ios_url' => ['nullable', 'url', 'max:2000', 'regex:/^https?:\/\/.+/'],
            'app_android_url' => ['nullable', 'url', 'max:2000', 'regex:/^https?:\/\/.+/'],
            'app_fallback_url' => ['nullable', 'url', 'max:2000', 'regex:/^https?:\/\/.+/'],

            'calendar_title' => ['nullable', 'string', 'max:255'],
            'calendar_start' => ['nullable', 'date'],
            'calendar_end' => ['nullable', 'date', 'after_or_equal:calendar_start'],
            'calendar_description' => ['nullable', 'string', 'max:1000'],
            'calendar_location' => ['nullable', 'string', 'max:500'],
            'calendar_all_day' => ['nullable', 'boolean'],

            'crypto_coin' => ['nullable', 'string', Rule::in(['bitcoin', 'ethereum', 'litecoin', 'dogecoin'])],
            'crypto_address' => ['nullable', 'string', 'min:20', 'max:200'],
            'crypto_amount' => ['nullable', 'numeric', 'min:0'],
            'crypto_label' => ['nullable', 'string', 'max:100'],
            'crypto_message' => ['nullable', 'string', 'max:255'],

            'review_platform' => ['nullable', Rule::in(['google', 'trustpilot', 'yelp', 'facebook', 'tripadvisor', 'other'])],
            'review_url' => ['nullable', 'url', 'max:2000', 'regex:/^https?:\/\/.+/'],
        ];
    }
}
