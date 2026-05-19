<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /** @var list<string> */
    private const SUPPORTED = ['pl', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('app.locale', 'pl'));

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'pl';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
