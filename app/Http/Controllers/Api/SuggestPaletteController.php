<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\QrCode\Ai\SuggestPaletteAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SuggestPaletteController extends Controller
{
    public function __invoke(Request $request, SuggestPaletteAction $action): JsonResponse
    {
        $validated = $request->validate([
            'logo_base64' => ['nullable', 'string', 'max:2097152'],
            'mime_type' => ['nullable', 'string', 'in:image/png,image/jpeg,image/gif,image/webp,image/svg+xml'],
        ]);

        return response()->json(
            $action->handle($validated['logo_base64'] ?? null, $validated['mime_type'] ?? 'image/png')
        );
    }
}
