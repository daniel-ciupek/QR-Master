<?php

declare(strict_types=1);

namespace App\Http\Controllers\BioLink;

use App\Actions\BioLink\UpdateBioLinkAction;
use App\Enums\BioLinkTemplate;
use App\Http\Controllers\Controller;
use App\Models\BioLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class BioLinkController extends Controller
{
    public function edit(BioLink $bioLink): Response
    {
        Gate::authorize('update', $bioLink);

        $bioLink->load('items');

        return Inertia::render('BioLink/Edit', [
            'bioLink' => [
                'id' => $bioLink->id,
                'slug' => $bioLink->slug,
                'title' => $bioLink->title,
                'bio' => $bioLink->bio,
                'template' => $bioLink->template->value,
                'theme' => $bioLink->resolvedTheme(),
                'avatar_url' => $bioLink->getFirstMediaUrl('avatar') ?: null,
                'public_url' => route('bio-link.show', $bioLink->slug),
            ],
            'items' => $bioLink->items->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'url' => $item->url,
                'icon' => $item->icon,
                'sort_order' => $item->sort_order,
                'is_active' => $item->is_active,
                'click_count' => $item->click_count,
            ])->values()->all(),
            'templates' => array_map(fn (BioLinkTemplate $t) => $t->value, BioLinkTemplate::cases()),
        ]);
    }

    public function update(Request $request, BioLink $bioLink, UpdateBioLinkAction $action): RedirectResponse
    {
        Gate::authorize('update', $bioLink);

        $validated = $request->validate([
            'bio' => ['nullable', 'string', 'max:500'],
            'template' => ['required', Rule::enum(BioLinkTemplate::class)],
            'theme' => ['nullable', 'array'],
            'theme.primary_color' => ['nullable', 'string', 'max:20'],
            'theme.bg_color' => ['nullable', 'string', 'max:20'],
            'theme.text_color' => ['nullable', 'string', 'max:20'],
            'theme.button_shape' => ['nullable', Rule::in(['rounded', 'square', 'pill'])],
            'theme.font' => ['nullable', Rule::in(['inter', 'serif', 'mono'])],
        ]);

        $action->handle(
            $bioLink,
            $validated['bio'] ?? null,
            BioLinkTemplate::from($validated['template']),
            $validated['theme'] ?? [],
        );

        return back()->with('success', 'Bio-Link zapisany.');
    }

    public function uploadAvatar(Request $request, BioLink $bioLink): RedirectResponse
    {
        Gate::authorize('update', $bioLink);
        $request->validate(['avatar' => ['required', 'file', 'image', 'max:2048']]);

        $bioLink->addMediaFromRequest('avatar')
            ->toMediaCollection('avatar');

        return back()->with('success', 'Awatar zaktualizowany.');
    }

    public function deleteAvatar(BioLink $bioLink): RedirectResponse
    {
        Gate::authorize('update', $bioLink);
        $bioLink->clearMediaCollection('avatar');

        return back()->with('success', 'Awatar usunięty.');
    }
}
