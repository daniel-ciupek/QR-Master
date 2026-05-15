<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tag;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Folders
 *
 * Manage folders (tags) to organise your QR codes.
 */
final class TagController extends Controller
{
    /**
     * List folders
     *
     * Returns all folders (tags) belonging to the authenticated user.
     *
     * @response 200 {"data": [{"id": 1, "name": "Marketing", "color": "#6366f1", "created_at": "2026-05-15T10:00:00.000000Z"}, {"id": 2, "name": "Events", "color": "#f59e0b", "created_at": "2026-05-15T11:00:00.000000Z"}]}
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tags = Tag::where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'color', 'created_at']);

        return response()->json(['data' => $tags]);
    }

    /**
     * Create folder
     *
     * Creates a new folder (tag) to organise QR codes.
     *
     * @bodyParam name string required Folder name (max 50 characters). Example: Marketing
     * @bodyParam color string Hex colour code (e.g. #6366f1). Example: #6366f1
     *
     * @response 201 {"data": {"id": 3, "name": "Marketing", "color": "#6366f1", "user_id": 1, "updated_at": "2026-05-15T12:00:00.000000Z", "created_at": "2026-05-15T12:00:00.000000Z"}}
     * @response 422 {"message": "The name field is required.", "errors": {"name": ["The name field is required."]}}
     */
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
