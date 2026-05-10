<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Actions\Profile\DeleteAccountAction;
use App\Actions\Profile\ExportUserDataAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ProfileController extends Controller
{
    public function show(Request $request): InertiaResponse
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Profile/Index', [
            'mustVerifyEmail' => ! $user->hasVerifiedEmail(),
        ]);
    }

    public function exportData(Request $request, ExportUserDataAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()
            ->json($action->handle($user), 200, [
                'Content-Disposition' => 'attachment; filename="qrmaster-data-export.json"',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function destroy(Request $request, DeleteAccountAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $action->handle($user, (string) $request->input('password', ''));

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
