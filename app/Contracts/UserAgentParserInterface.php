<?php

declare(strict_types=1);

namespace App\Contracts;

interface UserAgentParserInterface
{
    /**
     * @return array{device_type: string|null, os: string|null, browser: string|null}
     */
    public function parse(string $userAgent): array;
}
