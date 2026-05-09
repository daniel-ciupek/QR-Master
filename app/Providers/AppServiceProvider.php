<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
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
            // Pulse dostępny tylko w local/staging
            // Na produkcji: rozszerzyć o $user->hasRole('admin') po instalacji spatie/laravel-permission
            return ! app()->isProduction();
        });
    }
}
