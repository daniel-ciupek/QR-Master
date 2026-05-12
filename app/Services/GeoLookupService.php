<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GeoLookupInterface;
use Illuminate\Contracts\Cache\Repository;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

final class GeoLookupService implements GeoLookupInterface
{
    public function __construct(private readonly Repository $cache) {}

    /**
     * @return array{country: string|null, region: string|null, city: string|null, lat: float|null, lng: float|null}|null
     */
    public function lookup(string $ip): ?array
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP) || $ip === '127.0.0.1') {
            return null;
        }

        return $this->cache->remember(
            "geo_ip:{$ip}",
            now()->addHours(24),
            function () use ($ip): ?array {
                $pos = Location::get($ip);
                if (! ($pos instanceof Position)) {
                    return null;
                }

                return [
                    'country' => $pos->countryCode ?: null,
                    'region' => $pos->regionName ?: null,
                    'city' => $pos->cityName ?: null,
                    'lat' => $pos->latitude !== '' ? (float) $pos->latitude : null,
                    'lng' => $pos->longitude !== '' ? (float) $pos->longitude : null,
                ];
            }
        );
    }
}
