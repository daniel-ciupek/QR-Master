<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use App\Models\QrCode;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin QrCode */
final class QrCodeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'title' => $this->title,
            'slug' => $this->slug,
            'short_hash' => $this->short_hash,
            'destination_url' => $this->destination_url,
            'fallback_url' => $this->fallback_url,
            'is_active' => $this->is_active,
            'is_expired' => $this->isExpired(),
            'scan_count' => $this->scan_count,
            'scan_limit' => $this->scan_limit,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'activates_at' => $this->activates_at?->toIso8601String(),
            'redirect_url' => route('qr.redirect', $this->short_hash),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
            ])->values()),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
