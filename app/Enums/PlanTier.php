<?php

declare(strict_types=1);

namespace App\Enums;

enum PlanTier: string
{
    case Free = 'free';
    case Pro = 'pro';
    case Business = 'business';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Pro => 'Pro',
            self::Business => 'Business',
            self::Enterprise => 'Enterprise',
        };
    }

    public function monthlyPricePln(): int
    {
        return match ($this) {
            self::Free => 0,
            self::Pro => 4900,
            self::Business => 19900,
            self::Enterprise => 0,
        };
    }

    /** Maximum dynamic QR codes. null = unlimited. */
    public function maxDynamicQrCodes(): ?int
    {
        return match ($this) {
            self::Free => 5,
            self::Pro => 100,
            self::Business => 1000,
            self::Enterprise => null,
        };
    }

    /** Maximum scans per month. null = unlimited. */
    public function maxScansPerMonth(): ?int
    {
        return match ($this) {
            self::Free => 100,
            self::Pro => 50_000,
            self::Business => 1_000_000,
            self::Enterprise => null,
        };
    }

    public function canUseAnalytics(): bool
    {
        return $this !== self::Free;
    }

    public function canUseAbTest(): bool
    {
        return $this !== self::Free;
    }

    public function canUseSmartRedirect(): bool
    {
        return $this === self::Business || $this === self::Enterprise;
    }

    public function canUseApi(): bool
    {
        return $this === self::Business || $this === self::Enterprise;
    }

    public function canUseCustomBranding(): bool
    {
        return $this !== self::Free;
    }

    public function canUseWebhooks(): bool
    {
        return $this === self::Business || $this === self::Enterprise;
    }

    public function canUseBulkImport(): bool
    {
        return $this === self::Business || $this === self::Enterprise;
    }

    public function apiRequestsPerHour(): int
    {
        return match ($this) {
            self::Free => 60,
            self::Pro => 1_000,
            self::Business => 10_000,
            self::Enterprise => 100_000,
        };
    }
}
