<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\UserAgentParserInterface;
use WhichBrowser\Parser;

final class UserAgentParserService implements UserAgentParserInterface
{
    /**
     * @return array{device_type: string|null, os: string|null, browser: string|null}
     */
    public function parse(string $userAgent): array
    {
        if ($userAgent === '') {
            return ['device_type' => null, 'os' => null, 'browser' => null];
        }

        $result = new Parser(['User-Agent' => $userAgent]);

        return [
            'device_type' => $this->resolveDeviceType($result),
            'os' => $result->os->name ?: null,
            'browser' => $result->browser->name ?: null,
        ];
    }

    private function resolveDeviceType(Parser $result): ?string
    {
        return match (true) {
            $result->isType('mobile') => 'mobile',
            $result->isType('tablet') => 'tablet',
            $result->isType('desktop') => 'desktop',
            $result->isType('bot') => 'bot',
            default => null,
        };
    }
}
