<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\TurnstileService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ValidateTurnstile
{
    public function __construct(private readonly TurnstileService $turnstile) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldValidate($request)) {
            return $next($request);
        }

        $token = $request->string('turnstile_token')->toString();

        if (! $this->turnstile->verify($token, $request->ip())) {
            return back()
                ->withErrors(['turnstile_token' => 'Weryfikacja CAPTCHA nie powiodła się. Spróbuj ponownie.'])
                ->withInput();
        }

        return $next($request);
    }

    private function shouldValidate(Request $request): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        return $request->isMethod('POST')
            && in_array($request->path(), config('turnstile.protected_routes', []), true);
    }
}
