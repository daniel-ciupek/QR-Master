<?php

declare(strict_types=1);

namespace App\Http\Controllers\BioLink;

use App\Actions\BioLink\AddBioLinkItemAction;
use App\Actions\BioLink\DeleteBioLinkItemAction;
use App\Actions\BioLink\ReorderBioLinkItemsAction;
use App\Actions\BioLink\UpdateBioLinkItemAction;
use App\Http\Controllers\Controller;
use App\Models\BioLink;
use App\Models\BioLinkItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class BioLinkItemController extends Controller
{
    public function store(Request $request, BioLink $bioLink, AddBioLinkItemAction $action): RedirectResponse
    {
        Gate::authorize('update', $bioLink);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'url' => ['required', 'url', 'max:2000', 'regex:/^https?:\/\/.+/'],
            'icon' => ['nullable', 'string', 'max:20'],
        ]);

        $action->handle($bioLink, $validated['title'], $validated['url'], $validated['icon'] ?? null);

        return back()->with('success', 'Link dodany.');
    }

    public function update(
        Request $request,
        BioLink $bioLink,
        BioLinkItem $item,
        UpdateBioLinkItemAction $action,
    ): RedirectResponse {
        Gate::authorize('update', $bioLink);
        abort_if($item->bio_link_id !== $bioLink->id, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'url' => ['required', 'url', 'max:2000', 'regex:/^https?:\/\/.+/'],
            'icon' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $action->handle(
            $item,
            $validated['title'],
            $validated['url'],
            $validated['icon'] ?? null,
            (bool) ($validated['is_active'] ?? true),
        );

        return back()->with('success', 'Link zaktualizowany.');
    }

    public function destroy(BioLink $bioLink, BioLinkItem $item, DeleteBioLinkItemAction $action): RedirectResponse
    {
        Gate::authorize('update', $bioLink);
        abort_if($item->bio_link_id !== $bioLink->id, 403);

        $action->handle($item);

        return back()->with('success', 'Link usunięty.');
    }

    public function reorder(Request $request, BioLink $bioLink, ReorderBioLinkItemsAction $action): RedirectResponse
    {
        Gate::authorize('update', $bioLink);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $action->handle($bioLink, $validated['ids']);

        return back();
    }
}
