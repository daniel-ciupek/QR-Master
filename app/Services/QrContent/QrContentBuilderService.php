<?php

declare(strict_types=1);

namespace App\Services\QrContent;

use App\Enums\QrCodeType;

final class QrContentBuilderService
{
    public function __construct(
        private readonly VCardBuilder $vCardBuilder,
        private readonly WifiBuilder $wifiBuilder,
        private readonly CalendarBuilder $calendarBuilder,
        private readonly CryptoBuilder $cryptoBuilder,
    ) {}

    /**
     * Build the QR string content (destination_url) for a given type.
     *
     * @param  array<string, string|null>  $fields  Type-specific fields (non-sensitive + sensitive)
     */
    public function build(QrCodeType $type, array $fields): string
    {
        return match ($type) {
            QrCodeType::Url => $fields['destination_url'] ?? '',
            QrCodeType::Text => $fields['text_content'] ?? '',
            QrCodeType::Email => $this->buildEmail($fields),
            QrCodeType::Phone => 'tel:'.($fields['phone_number'] ?? ''),
            QrCodeType::Sms => $this->buildSms($fields),
            QrCodeType::VCard => $this->vCardBuilder->build(
                $fields,
                $fields['vcard_phone'] ?? null,
                $fields['vcard_email'] ?? null,
            ),
            QrCodeType::Wifi => $this->wifiBuilder->build($fields, $fields['wifi_password'] ?? null),
            QrCodeType::Geo => $this->buildGeo($fields),
            // PDF viewer URL is self-referential — set after QrCode creation in controller
            QrCodeType::Pdf => '',
            // Bio-Link public URL is set after BioLink creation in controller
            QrCodeType::BioLink => '',
            // App Store redirect URL is the dynamic QR hash — set after creation
            QrCodeType::App => '',
            QrCodeType::Calendar => $this->calendarBuilder->build($fields),
            QrCodeType::Crypto => $this->cryptoBuilder->build($fields),
            QrCodeType::Review => (string) ($fields['review_url'] ?? ''),
        };
    }

    /** @param array<string, string|null> $fields */
    private function buildEmail(array $fields): string
    {
        $address = $fields['email_address'] ?? '';
        if ($address === '') {
            return '';
        }
        $params = [];
        if (($fields['email_subject'] ?? '') !== '') {
            $params[] = 'subject='.rawurlencode($fields['email_subject'] ?? '');
        }
        if (($fields['email_body'] ?? '') !== '') {
            $params[] = 'body='.rawurlencode($fields['email_body'] ?? '');
        }

        return 'mailto:'.$address.($params !== [] ? '?'.implode('&', $params) : '');
    }

    /** @param array<string, string|null> $fields */
    private function buildSms(array $fields): string
    {
        $number = $fields['sms_number'] ?? '';
        if ($number === '') {
            return '';
        }
        $message = $fields['sms_message'] ?? '';

        return $message !== '' ? "smsto:{$number}:{$message}" : "smsto:{$number}";
    }

    /** @param array<string, string|null> $fields */
    private function buildGeo(array $fields): string
    {
        $lat = (string) ($fields['geo_lat'] ?? '');
        $lng = (string) ($fields['geo_lng'] ?? '');

        if ($lat === '' || $lng === '') {
            return '';
        }

        return "geo:{$lat},{$lng}";
    }
}
