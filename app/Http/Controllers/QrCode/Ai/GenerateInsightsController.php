<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrCode\Ai;

use App\Actions\QrCode\Ai\GenerateInsightsAction;
use App\Http\Controllers\Controller;
use App\Models\QrCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class GenerateInsightsController extends Controller
{
    public function __invoke(QrCode $qrCode, GenerateInsightsAction $action): JsonResponse
    {
        Gate::authorize('view', $qrCode);

        return response()->json($action->handle($qrCode));
    }
}
