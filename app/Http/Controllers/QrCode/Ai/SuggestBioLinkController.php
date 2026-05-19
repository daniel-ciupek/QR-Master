<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrCode\Ai;

use App\Actions\QrCode\Ai\SuggestBioLinkAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SuggestBioLinkController extends Controller
{
    public function __invoke(Request $request, SuggestBioLinkAction $action): JsonResponse
    {
        $validated = $request->validate([
            'profession' => ['required', 'string', 'max:200'],
        ]);

        return response()->json($action->handle($validated['profession']));
    }
}
