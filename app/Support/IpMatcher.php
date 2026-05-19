<?php

declare(strict_types=1);

namespace App\Support;

final class IpMatcher
{
    /**
     * Check if $ip matches any entry in $allowlist (exact IP or CIDR notation).
     *
     * @param  list<string>  $allowlist
     */
    public static function matches(string $ip, array $allowlist): bool
    {
        foreach ($allowlist as $entry) {
            $entry = trim($entry);

            if ($entry === '') {
                continue;
            }

            if (str_contains($entry, '/')) {
                if (self::ipInCidr($ip, $entry)) {
                    return true;
                }
            } elseif ($ip === $entry) {
                return true;
            }
        }

        return false;
    }

    private static function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits = (int) $bits;

        // IPv6
        if (str_contains($ip, ':') || str_contains($subnet, ':')) {
            return self::ipv6InCidr($ip, $subnet, $bits);
        }

        // IPv4
        if ($bits < 0 || $bits > 32) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = $bits === 0 ? 0 : (~0 << (32 - $bits));

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    private static function ipv6InCidr(string $ip, string $subnet, int $bits): bool
    {
        if ($bits < 0 || $bits > 128) {
            return false;
        }

        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        $ipPacked = unpack('C*', $ipBin);
        $subnetPacked = unpack('C*', $subnetBin);

        if (! is_array($ipPacked) || ! is_array($subnetPacked)) {
            return false;
        }

        $ipBytes = array_values($ipPacked);
        $subnetBytes = array_values($subnetPacked);

        $fullBytes = intdiv($bits, 8);
        $remainingBits = $bits % 8;

        for ($i = 0; $i < $fullBytes; $i++) {
            if ($ipBytes[$i] !== $subnetBytes[$i]) {
                return false;
            }
        }

        if ($remainingBits > 0 && $fullBytes < 16) {
            $mask = 0xFF & (0xFF << (8 - $remainingBits));

            if (($ipBytes[$fullBytes] & $mask) !== ($subnetBytes[$fullBytes] & $mask)) {
                return false;
            }
        }

        return true;
    }
}
