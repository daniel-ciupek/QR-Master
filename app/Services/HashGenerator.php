<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\QrCode;
use RuntimeException;

final class HashGenerator
{
    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private const LENGTH = 8;

    private const MAX_TRIES = 10;

    /**
     * Generate a unique, cryptographically random base62 short hash.
     *
     * Collision probability at 1M codes: ~(1M^2) / (2 × 62^8) ≈ 0.23% — negligible.
     * Re-tries up to MAX_TRIES on the rare collision before throwing.
     *
     * @throws RuntimeException when MAX_TRIES collisions occur consecutively
     */
    public function generate(): string
    {
        for ($i = 0; $i < self::MAX_TRIES; $i++) {
            $hash = $this->random();

            if (! QrCode::withTrashed()->where('short_hash', $hash)->exists()) {
                return $hash;
            }
        }

        throw new RuntimeException('Could not generate a unique short hash after '.self::MAX_TRIES.' attempts.');
    }

    private function random(): string
    {
        $bytes = random_bytes(self::LENGTH);
        $base = strlen(self::ALPHABET);
        $result = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $result .= self::ALPHABET[ord($bytes[$i]) % $base];
        }

        return $result;
    }
}
