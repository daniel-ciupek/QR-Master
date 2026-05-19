<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrCode\Ai;

use App\Actions\QrCode\Ai\SemanticSearchAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SemanticSearchController extends Controller
{
    public function __invoke(Request $request, SemanticSearchAction $action): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:200'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $results = $action->handle($user, $validated['q']);

        return response()->json([
            'results' => $results->map(fn ($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'type' => $r->type,
                'short_hash' => $r->short_hash,
                'similarity' => round((float) $r->similarity, 3),
            ])->values(),
        ]);
    }
}
