<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrCode\Ai;

use App\Actions\QrCode\Ai\SuggestQrNameAction;
use App\Http\Controllers\Controller;
use App\Models\QrCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class SuggestQrNameController extends Controller
{
    public function __invoke(QrCode $qrCode, SuggestQrNameAction $action): JsonResponse
    {
        Gate::authorize('update', $qrCode);

        return response()->json($action->handle($qrCode));
    }
}
