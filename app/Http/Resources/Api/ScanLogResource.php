<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use App\Models\ScanLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ScanLog */
final class ScanLogResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scanned_at' => $this->scanned_at->toIso8601String(),
            'country' => $this->country,
            'region' => $this->region,
            'city' => $this->city,
            'device_type' => $this->device_type,
            'os' => $this->os,
            'browser' => $this->browser,
            'referrer' => $this->referrer,
            'language' => $this->language,
        ];
    }
}
