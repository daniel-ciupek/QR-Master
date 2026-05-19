<?php

declare(strict_types=1);

namespace App\Actions\QrCode\Ai;

use App\Services\AiService;

final class SuggestPaletteAction
{
    public function __construct(private readonly AiService $ai) {}

    /**
     * @return array{palettes: list<array{name: string, dotColor: string, bgColor: string}>}
     */
    public function handle(?string $base64Image, string $mimeType = 'image/png'): array
    {
        if ($base64Image === null || $base64Image === '') {
            return ['palettes' => $this->staticFallback()];
        }

        return $this->ai->suggestPalettesFromLogo($base64Image, $mimeType);
    }

    /**
     * @return list<array{name: string, dotColor: string, bgColor: string}>
     */
    private function staticFallback(): array
    {
        return [
            ['name' => 'Classic', 'dotColor' => '#000000', 'bgColor' => '#ffffff'],
            ['name' => 'Navy', 'dotColor' => '#1e3a5f', 'bgColor' => '#ffffff'],
            ['name' => 'Forest', 'dotColor' => '#166534', 'bgColor' => '#f0fdf4'],
            ['name' => 'Crimson', 'dotColor' => '#991b1b', 'bgColor' => '#fff1f2'],
            ['name' => 'Violet', 'dotColor' => '#5b21b6', 'bgColor' => '#f5f3ff'],
        ];
    }
}
