<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\GeoLookupInterface;
use App\Contracts\UserAgentParserInterface;
use App\Listeners\StartProTrialOnRegistration;
use App\Models\QrCode;
use App\Observers\QrCodeObserver;
use App\Services\GeoLookupService;
use App\Services\UserAgentParserService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Sentry\State\Scope;

class AppServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->bind(GeoLookupInterface::class, GeoLookupService::class);
        $this->app->bind(UserAgentParserInterface::class, UserAgentParserService::class);
    }

    public function boot(): void
    {
        QrCode::observe(QrCodeObserver::class);
        $this->configureSentry();
        $this->configurePulseGate();
        $this->configureRateLimiters();
        $this->configureEvents();
    }

    private function configureSentry(): void
    {
        if (! app()->bound('sentry')) {
            return;
        }

        \Sentry\configureScope(function (Scope $scope): void {
            // Nie dołączamy domyślnie danych użytkownika (PII)
        });
    }

    private function configurePulseGate(): void
    {
        Gate::define('viewPulse', function (): bool {
            return ! app()->isProduction();
        });
    }

    private function configureEvents(): void
    {
        Event::listen(Registered::class, StartProTrialOnRegistration::class);
    }

    private function configureRateLimiters(): void
    {
        // /q/{hash} — anti-DDoS, 60 req/min per IP
        RateLimiter::for('public-redirect', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // /q/{hash}/unlock — brute-force protection, 5 attempts per minute per IP
        RateLimiter::for('qr-password', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // API — per-user, per-hour quota based on plan tier
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();

            if ($user === null) {
                return Limit::perHour(0)->by($request->ip());
            }

            return Limit::perHour($user->plan_tier->apiRequestsPerHour())
                ->by('api:'.$user->id);
        });

        // WebAuthn login — 10 attempts per minute per IP (brute-force protection)
        RateLimiter::for('webauthn-login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // WebAuthn register — 5 attempts per minute per authenticated user
        RateLimiter::for('webauthn-register', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(5)->by($user ? 'webauthn:'.$user->id : $request->ip());
        });
    }
}
