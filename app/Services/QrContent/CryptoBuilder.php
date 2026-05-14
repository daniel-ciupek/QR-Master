<?php

declare(strict_types=1);

namespace App\Services\QrContent;

final class CryptoBuilder
{
    /** @param array<string, mixed> $fields */
    public function build(array $fields): string
    {
        $coin = strtolower((string) ($fields['crypto_coin'] ?? 'bitcoin'));
        $address = trim((string) ($fields['crypto_address'] ?? ''));

        if ($address === '') {
            return '';
        }

        $params = [];

        $amount = (string) ($fields['crypto_amount'] ?? '');
        if ($amount !== '' && is_numeric($amount) && (float) $amount > 0) {
            $params['amount'] = $amount;
        }

        $label = (string) ($fields['crypto_label'] ?? '');
        if ($label !== '') {
            $params['label'] = $label;
        }

        $message = (string) ($fields['crypto_message'] ?? '');
        if ($message !== '') {
            $params['message'] = $message;
        }

        $uri = "{$coin}:{$address}";

        if ($params !== []) {
            $uri .= '?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        return $uri;
    }
}
