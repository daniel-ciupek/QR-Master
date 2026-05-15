<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tokens;

use App\Enums\ApiAbility;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ApiTokenController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $tokens = $user->tokens()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at'])
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at?->toDateString(),
                'expires_at' => $token->expires_at?->toDateString(),
                'created_at' => $token->created_at?->toDateString(),
            ]);

        return Inertia::render('ApiTokens/Index', [
            'tokens' => $tokens,
            'availableAbilities' => collect(ApiAbility::all())
                ->map(fn (ApiAbility $a) => ['value' => $a->value, 'label' => $a->label()])
                ->values(),
            'newTokenValue' => session('new_token_value'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['string', 'in:'.implode(',', array_column(ApiAbility::all(), 'value'))],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $expiresAt = isset($validated['expires_at'])
            ? now()->parse($validated['expires_at'])->endOfDay()
            : null;

        $token = $user->createToken(
            $validated['name'],
            $validated['abilities'],
            $expiresAt,
        );

        return redirect()->route('api-tokens.index')
            ->with('new_token_value', $token->plainTextToken);
    }

    public function destroy(Request $request, int $tokenId): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->tokens()->where('id', $tokenId)->delete();

        return redirect()->route('api-tokens.index');
    }
}
