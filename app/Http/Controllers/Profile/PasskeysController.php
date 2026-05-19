<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PasskeysController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Profile/Passkeys', [
            'credentials' => $user->webAuthnCredentials()
                ->select(['id', 'alias', 'transports', 'created_at', 'updated_at'])
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->alias ?? 'Passkey',
                    'transports' => $c->transports,
                    'created_at' => $c->created_at->toIso8601String(),
                ]),
        ]);
    }
}
