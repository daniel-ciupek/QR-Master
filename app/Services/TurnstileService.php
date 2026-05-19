<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class TurnstileService
{
    public function verify(string $token, ?string $ip = null): bool
    {
        $secretKey = config('turnstile.secret_key');

        if (empty($secretKey)) {
            return true;
        }

        $payload = ['secret' => $secretKey, 'response' => $token];

        if ($ip !== null) {
            $payload['remoteip'] = $ip;
        }

        $response = Http::asForm()->post(config('turnstile.verify_url'), $payload);

        return (bool) $response->json('success', false);
    }
}
