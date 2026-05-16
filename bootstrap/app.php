<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureAiRateLimit;
use App\Http\Middleware\EnsureOnboardingComplete;
use App\Http\Middleware\EnsurePlanFeature;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackAffiliateRef;
use App\Http\Middleware\TrackUserSession;
use App\Http\Middleware\ValidateTurnstile;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Csp\AddCspHeaders;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
            AddCspHeaders::class,
            SetLocale::class,
            HandleInertiaRequests::class,
            ValidateTurnstile::class,
            TrackUserSession::class,
            EnsureOnboardingComplete::class,
            TrackAffiliateRef::class,
        ]);

        $middleware->alias([
            'plan.feature' => EnsurePlanFeature::class,
            'ai.rate-limit' => EnsureAiRateLimit::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
