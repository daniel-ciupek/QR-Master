<?php

declare(strict_types=1);

namespace App\Enums;

enum ApiAbility: string
{
    case QrCodesRead = 'qrcodes:read';
    case QrCodesWrite = 'qrcodes:write';
    case AnalyticsRead = 'analytics:read';

    public function label(): string
    {
        return match ($this) {
            self::QrCodesRead => 'Read QR codes',
            self::QrCodesWrite => 'Create & update QR codes',
            self::AnalyticsRead => 'Read analytics & scan data',
        };
    }

    /** @return list<self> */
    public static function all(): array
    {
        return [self::QrCodesRead, self::QrCodesWrite, self::AnalyticsRead];
    }
}
