<?php

declare(strict_types=1);

namespace App\Contracts;

interface GeoLookupInterface
{
    /**
     * @return array{country: string|null, region: string|null, city: string|null, lat: float|null, lng: float|null}|null
     */
    public function lookup(string $ip): ?array;
}
