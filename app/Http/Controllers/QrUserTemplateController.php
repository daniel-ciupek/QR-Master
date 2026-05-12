<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\QrUserTemplate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class QrUserTemplateController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'settings' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();

        QrUserTemplate::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'settings' => json_decode($validated['settings'], true) ?? [],
        ]);

        return back();
    }

    public function destroy(QrUserTemplate $qrUserTemplate): RedirectResponse
    {
        Gate::authorize('delete', $qrUserTemplate);
        $qrUserTemplate->delete();

        return back();
    }
}
