<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        /** @var Team|null $currentTeam */
        $currentTeam = $request->attributes->get('current_team');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                    'two_factor_enabled' => $user->hasEnabledTwoFactorAuthentication(),
                    'two_factor_confirmed_at' => $user->two_factor_confirmed_at?->toISOString(),
                    'current_team_id' => $user->current_team_id,
                ] : null,
            ],
            'current_team' => $currentTeam ? [
                'id' => $currentTeam->id,
                'name' => $currentTeam->name,
                'slug' => $currentTeam->slug,
            ] : null,
            'branding' => $currentTeam ? $currentTeam->brandingSettings() : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
            'locale' => app()->getLocale(),
        ];
    }
}
