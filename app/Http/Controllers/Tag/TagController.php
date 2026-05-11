<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tag;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class TagController extends Controller
{
    public function store(StoreTagRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        Tag::create([
            'user_id' => $user->id,
            'name' => $request->string('name')->trim()->toString(),
            'color' => $request->string('color')->toString(),
        ]);

        return back();
    }

    public function destroy(Request $request, Tag $tag): RedirectResponse
    {
        Gate::authorize('delete', $tag);
        $tag->delete();

        return back();
    }
}
