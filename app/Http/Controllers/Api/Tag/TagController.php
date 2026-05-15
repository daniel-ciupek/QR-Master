<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tag;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tags = Tag::where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'color', 'created_at']);

        return response()->json(['data' => $tags]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $tag = Tag::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#6366f1',
        ]);

        return response()->json(['data' => $tag], 201);
    }
}
