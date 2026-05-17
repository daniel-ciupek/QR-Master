<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidIpOrCidr implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a valid IP address or CIDR range.');

            return;
        }

        $value = trim($value);

        if (str_contains($value, '/')) {
            $parts = explode('/', $value, 2);
            $addr = $parts[0];
            $bits = (int) $parts[1];

            if (str_contains($addr, ':')) {
                if (inet_pton($addr) === false || $bits < 0 || $bits > 128) {
                    $fail('The :attribute must be a valid IPv6 CIDR range (e.g. 2001:db8::/32).');
                }
            } else {
                if (filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false || $bits < 0 || $bits > 32) {
                    $fail('The :attribute must be a valid IPv4 CIDR range (e.g. 192.168.1.0/24).');
                }
            }
        } else {
            if (filter_var($value, FILTER_VALIDATE_IP) === false) {
                $fail('The :attribute must be a valid IPv4 or IPv6 address.');
            }
        }
    }
}
