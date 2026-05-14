<?php

declare(strict_types=1);

namespace App\Http\Controllers\BioLink;

use App\Http\Controllers\Controller;
use App\Models\BioLink;
use App\Models\BioLinkItem;
use Illuminate\Http\RedirectResponse;

final class BioLinkClickController extends Controller
{
    public function __invoke(string $slug, BioLinkItem $item): RedirectResponse
    {
        $bioLink = BioLink::where('slug', $slug)->firstOrFail();

        // Ensure item belongs to this bio-link
        abort_if($item->bio_link_id !== $bioLink->id, 404);
        abort_if(! $item->is_active, 404);

        $item->increment('click_count');

        return redirect()->away($item->url, 302)->withHeaders([
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
