<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrCode\Ai;

use App\Actions\QrCode\Ai\SuggestCtaAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SuggestCtaController extends Controller
{
    public function __invoke(Request $request, SuggestCtaAction $action): JsonResponse
    {
        $validated = $request->validate([
            'context' => ['required', 'string', 'max:500'],
        ]);

        return response()->json($action->handle($validated['context']));
    }
}
