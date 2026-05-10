<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class OnboardingController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Onboarding');
    }

    public function complete(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->forceFill(['onboarding_completed_at' => now()])->save();

        return redirect()->route('dashboard');
    }
}
