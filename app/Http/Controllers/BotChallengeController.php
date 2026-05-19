<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\QrCode;
use App\Services\TurnstileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BotChallengeController extends Controller
{
    public function show(string $hash): Response
    {
        $qrCode = QrCode::active()->where('short_hash', $hash)->first();

        if ($qrCode === null) {
            abort(404);
        }

        return Inertia::render('QrCode/BotChallenge', [
            'hash' => $hash,
            'title' => $qrCode->title,
            'siteKey' => config('turnstile.site_key', ''),
        ]);
    }

    public function verify(Request $request, string $hash, TurnstileService $turnstile): RedirectResponse
    {
        $request->validate(['turnstile_token' => ['required', 'string']]);

        if (! $turnstile->verify($request->string('turnstile_token')->toString(), $request->ip())) {
            return back()->withErrors(['turnstile_token' => __('bot.wrongToken')]);
        }

        $sessionKey = 'turnstile_bot_verified_'.hash('sha256', $request->ip() ?? '');
        $request->session()->put($sessionKey, true);

        return redirect()->route('qr.redirect', $hash);
    }
}
