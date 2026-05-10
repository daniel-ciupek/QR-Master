<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Sentry\State\Scope;

class AppServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureSentry();
        $this->configurePulseGate();
        $this->configureRateLimiters();
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

    private function configureRateLimiters(): void
    {
        // /q/{hash} — anti-DDoS, 60 req/min per IP
        RateLimiter::for('public-redirect', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
