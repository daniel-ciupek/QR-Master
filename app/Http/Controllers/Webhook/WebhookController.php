<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class WebhookController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $webhooks = Webhook::where('user_id', $user->id)
            ->withCount('deliveries')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Webhook $wh) => [
                'id' => $wh->id,
                'url' => $wh->url,
                'events' => $wh->events,
                'is_active' => $wh->is_active,
                'deliveries_count' => $wh->deliveries_count,
                'created_at' => $wh->created_at->toDateString(),
            ]);

        return Inertia::render('Webhooks/Index', [
            'webhooks' => $webhooks,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2000', 'regex:/^https?:\/\/.+/'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => [Rule::in(['qr.scanned'])],
        ]);

        /** @var User $user */
        $user = $request->user();

        Webhook::create([
            'user_id' => $user->id,
            'url' => $validated['url'],
            'secret' => Str::random(32),
            'events' => $validated['events'],
            'is_active' => true,
        ]);

        return redirect()->route('webhooks.index')->with('success', 'Webhook created.');
    }

    public function destroy(Request $request, Webhook $webhook): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($webhook->user_id !== $user->id) {
            abort(403);
        }

        $webhook->delete();

        return redirect()->route('webhooks.index');
    }

    public function deliveries(Request $request, Webhook $webhook): Response
    {
        /** @var User $user */
        $user = $request->user();

        if ($webhook->user_id !== $user->id) {
            abort(403);
        }

        $deliveries = $webhook->deliveries()
            ->orderByDesc('created_at')
            ->paginate(25)
            ->through(fn ($d) => [
                'id' => $d->id,
                'event' => $d->event,
                'status' => $d->status,
                'attempts' => $d->attempts,
                'response_status' => $d->response_status,
                'response_body' => $d->response_body ? mb_substr($d->response_body, 0, 200) : null,
                'delivered_at' => $d->delivered_at?->toIso8601String(),
                'last_attempted_at' => $d->last_attempted_at?->toIso8601String(),
                'created_at' => $d->created_at->toIso8601String(),
            ]);

        return Inertia::render('Webhooks/Deliveries', [
            'webhook' => ['id' => $webhook->id, 'url' => $webhook->url],
            'deliveries' => $deliveries,
        ]);
    }
}
