<?php

declare(strict_types=1);

namespace App\Http\Controllers\BioLink;

use App\Http\Controllers\Controller;
use App\Models\BioLink;
use Inertia\Inertia;
use Inertia\Response;

final class BioLinkPublicController extends Controller
{
    public function __invoke(string $slug): Response
    {
        $bioLink = BioLink::where('slug', $slug)->firstOrFail();

        $items = $bioLink->items()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'icon' => $item->icon,
                'click_url' => route('bio-link.click', [$slug, $item->id]),
            ])
            ->all();

        return Inertia::render('BioLink/Public', [
            'bioLink' => [
                'title' => $bioLink->title,
                'bio' => $bioLink->bio,
                'template' => $bioLink->template->value,
                'theme' => $bioLink->resolvedTheme(),
                'avatar_url' => $bioLink->getFirstMediaUrl('avatar') ?: null,
            ],
            'items' => $items,
        ]);
    }
}
