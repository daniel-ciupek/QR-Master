<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SsoConnection;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

final class SsoAuthController extends Controller
{
    public function detect(Request $request): RedirectResponse
    {
        $email = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ])['email'];

        $connection = SsoConnection::findByEmail($email);

        if ($connection === null) {
            return redirect()->route('login')->withErrors(['email' => 'No SSO connection found for this email domain.']);
        }

        $request->session()->put('sso_team_id', $connection->team_id);
        $request->session()->put('sso_email_hint', $email);

        return redirect()->route('sso.redirect', ['provider' => $connection->provider]);
    }

    public function redirect(string $provider): RedirectResponse|SymfonyRedirect
    {
        $connection = $this->resolveConnectionFromSession($provider);

        if ($connection === null) {
            return redirect()->route('login')->withErrors(['email' => 'SSO session expired. Please try again.']);
        }

        $this->configureDynamicProvider($provider, $connection);

        $driver = Socialite::driver($provider);

        if (method_exists($driver, 'scopes')) {
            $driver->scopes($this->scopesFor($provider));
        }

        return $driver->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $connection = $this->resolveConnectionFromSession($provider);

        if ($connection === null) {
            return redirect()->route('login')->withErrors(['email' => 'SSO session expired.']);
        }

        $this->configureDynamicProvider($provider, $connection);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors(['email' => 'SSO authentication failed. Please try again.']);
        }

        if ($socialUser->getEmail() === null) {
            return redirect()->route('login')->withErrors(['email' => 'No email returned from SSO provider.']);
        }

        $email = strtolower($socialUser->getEmail());
        $domain = strtolower(substr($email, strpos($email, '@') + 1));

        if ($domain !== $connection->email_domain) {
            return redirect()->route('login')->withErrors(['email' => 'Email domain does not match the SSO connection.']);
        }

        $user = User::where('email', $email)->first();

        if ($user === null) {
            if (! $connection->auto_provision) {
                return redirect()->route('login')->withErrors(['email' => 'Your account does not exist. Contact your workspace admin.']);
            }

            $user = User::create([
                'name' => $socialUser->getName() ?? $email,
                'email' => $email,
                'password' => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
                'onboarding_completed_at' => now(),
            ]);
        }

        $team = $connection->team;

        if ($team !== null && ! $user->belongsToTeam($team)) {
            $team->members()->attach($user->id, [
                'role' => 'viewer',
                'joined_at' => now(),
            ]);
        }

        if ($team !== null && $user->current_team_id === null) {
            $user->update(['current_team_id' => $team->id]);
        }

        $request->session()->forget(['sso_team_id', 'sso_email_hint']);

        Auth::login($user, remember: true);

        if ($team !== null) {
            AuditLogger::log($team, 'sso.login', "User {$user->email} authenticated via SSO ({$provider}).", $user);
        }

        return redirect()->intended(route('dashboard'));
    }

    private function resolveConnectionFromSession(string $provider): ?SsoConnection
    {
        $teamId = session('sso_team_id');

        if (! is_int($teamId) && ! is_string($teamId)) {
            return null;
        }

        return SsoConnection::where('team_id', (int) $teamId)
            ->where('provider', $provider)
            ->where('is_active', true)
            ->first();
    }

    private function configureDynamicProvider(string $provider, SsoConnection $connection): void
    {
        $base = [
            'client_id' => $connection->client_id,
            'client_secret' => $connection->client_secret,
            'redirect' => route('sso.callback', ['provider' => $provider]),
        ];

        $extra = match ($provider) {
            'azure' => ['tenant' => $connection->tenant_id ?? 'common'],
            'okta' => ['base_url' => 'https://'.($connection->okta_domain ?? 'your-domain.okta.com')],
            default => [],
        };

        Config::set("services.{$provider}", [...$base, ...$extra]);
    }

    /** @return list<string> */
    private function scopesFor(string $provider): array
    {
        return match ($provider) {
            'azure' => ['openid', 'profile', 'email', 'User.Read'],
            'okta' => ['openid', 'profile', 'email'],
            default => ['openid', 'profile', 'email'],
        };
    }
}
