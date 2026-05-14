<?php

declare(strict_types=1);

namespace App\Services\QrContent;

final class WifiBuilder
{
    private const SECURITY_MAP = [
        'wpa' => 'WPA',
        'wpa2' => 'WPA2',
        'wpa3' => 'WPA3',
        'wep' => 'WEP',
        'open' => 'nopass',
    ];

    /**
     * Generate a WiFi QR string (WIFI:T:...;S:...;P:...;H:...;;).
     *
     * @param  array<string, string|null>  $fields  Non-sensitive settings fields
     * @param  string|null  $password  Decrypted WiFi password from encrypted column
     */
    public function build(array $fields, ?string $password): string
    {
        $ssid = $this->escape($fields['wifi_ssid'] ?? '');
        $security = self::SECURITY_MAP[$fields['wifi_security'] ?? 'wpa'] ?? 'WPA';
        $hidden = ($fields['wifi_hidden'] ?? false) ? 'true' : 'false';
        $pass = $this->escape($password ?? '');

        return "WIFI:T:{$security};S:{$ssid};P:{$pass};H:{$hidden};;";
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', ';', ',', '"'], ['\\\\', '\\;', '\\,', '\\"'], $value);
    }
}
