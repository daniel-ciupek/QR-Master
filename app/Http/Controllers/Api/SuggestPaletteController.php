<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

// Placeholder for Etap 10 — will be replaced with Claude vision analysis
final class SuggestPaletteController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'palettes' => [
                ['name' => 'Classic', 'dotColor' => '#000000', 'bgColor' => '#ffffff'],
                ['name' => 'Navy', 'dotColor' => '#1e3a5f', 'bgColor' => '#ffffff'],
                ['name' => 'Forest', 'dotColor' => '#166534', 'bgColor' => '#f0fdf4'],
                ['name' => 'Crimson', 'dotColor' => '#991b1b', 'bgColor' => '#fff1f2'],
                ['name' => 'Violet', 'dotColor' => '#5b21b6', 'bgColor' => '#f5f3ff'],
                ['name' => 'Midnight', 'dotColor' => '#ffffff', 'bgColor' => '#0f172a'],
                ['name' => 'Ocean', 'dotColor' => '#0c4a6e', 'bgColor' => '#e0f2fe'],
                ['name' => 'Sunset', 'dotColor' => '#7c2d12', 'bgColor' => '#fff7ed'],
            ],
        ]);
    }
}
