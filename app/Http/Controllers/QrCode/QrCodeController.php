<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrCode;

use App\Actions\QrCode\DeleteQrCodeAction;
use App\Actions\QrCode\DuplicateQrCodeAction;
use App\Actions\QrCode\SyncQrCodeTagsAction;
use App\Actions\QrCode\ToggleActiveQrCodeAction;
use App\Actions\QrCode\UpdateQrCodeAction;
use App\Data\QrCodeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\QrCode\ExportQrRequest;
use App\Http\Requests\QrCode\UpdateQrCodeRequest;
use App\Models\QrCode;
use App\Models\Tag;
use App\Models\User;
use App\Services\QrRendering\QrRenderer;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class QrCodeController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $search = $request->string('search')->trim()->toString();
        $sort = $request->string('sort', 'created_at')->toString();
        $direction = $request->string('direction', 'desc')->toString();
        $tagId = (int) $request->input('tag_id', 0);

        $allowed = ['title', 'type', 'is_active', 'created_at', 'expires_at'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'created_at';
        }
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        $qrCodes = QrCode::forUser($user->id)
            ->with('tags')
            ->when($search !== '', fn ($q) => $q->where('title', 'ilike', "%{$search}%"))
            ->when($tagId > 0, fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('tags.id', $tagId)))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (QrCode $qr) => [
                'id' => $qr->id,
                'title' => $qr->title,
                'type' => $qr->type->value,
                'short_hash' => $qr->short_hash,
                'destination_url' => $qr->destination_url,
                'is_active' => $qr->is_active,
                'is_expired' => $qr->isExpired(),
                'expires_at' => $qr->expires_at?->toDateString(),
                'created_at' => $qr->created_at->toDateString(),
                'tags' => $qr->tags->map(fn (Tag $tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'color' => $tag->color,
                ])->all(),
            ]);

        $userTags = Tag::where('user_id', $user->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Tag $tag) => ['id' => $tag->id, 'name' => $tag->name, 'color' => $tag->color])
            ->all();

        return Inertia::render('QrCode/Index', [
            'qrCodes' => $qrCodes,
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction, 'tag_id' => $tagId],
            'userTags' => $userTags,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('QrCode/Create');
    }

    public function edit(Request $request, QrCode $qrCode): Response
    {
        Gate::authorize('update', $qrCode);

        /** @var User $user */
        $user = $request->user();

        $qrCode->load('tags');

        $userTags = Tag::where('user_id', $user->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Tag $tag) => ['id' => $tag->id, 'name' => $tag->name, 'color' => $tag->color])
            ->all();

        return Inertia::render('QrCode/Edit', [
            'qrCode' => [
                'id' => $qrCode->id,
                'title' => $qrCode->title,
                'type' => $qrCode->type->value,
                'short_hash' => $qrCode->short_hash,
                'destination_url' => $qrCode->destination_url,
                'is_active' => $qrCode->is_active,
                'expires_at' => $qrCode->expires_at?->toDateString(),
                'tag_ids' => $qrCode->tags->pluck('id')->toArray(),
            ],
            'userTags' => $userTags,
        ]);
    }

    public function update(
        UpdateQrCodeRequest $request,
        QrCode $qrCode,
        UpdateQrCodeAction $action,
        SyncQrCodeTagsAction $syncTags,
    ): RedirectResponse {
        Gate::authorize('update', $qrCode);

        /** @var User $user */
        $user = $request->user();

        $data = new QrCodeData(
            title: $request->string('title')->trim()->toString(),
            type: $qrCode->type,
            destination_url: $request->filled('destination_url')
                ? $request->string('destination_url')->trim()->toString()
                : null,
            settings: $qrCode->settings,
            is_active: $request->boolean('is_active'),
            expires_at: $request->filled('expires_at')
                ? Carbon::parse($request->input('expires_at'))
                : null,
        );

        $action->handle($qrCode, $data);
        $syncTags->handle($qrCode, $user, $request->array('tag_ids'));

        return redirect()->route('qr.index')->with('success', 'Zmiany zostały zapisane.');
    }

    public function destroy(QrCode $qrCode, DeleteQrCodeAction $action): RedirectResponse
    {
        Gate::authorize('delete', $qrCode);
        $action->handle($qrCode);

        return redirect()->route('qr.index')->with('success', 'Kod QR został usunięty.');
    }

    public function toggleActive(QrCode $qrCode, ToggleActiveQrCodeAction $action): RedirectResponse
    {
        Gate::authorize('update', $qrCode);
        $action->handle($qrCode);

        return back();
    }

    public function duplicate(Request $request, QrCode $qrCode, DuplicateQrCodeAction $action): RedirectResponse
    {
        Gate::authorize('duplicate', $qrCode);

        /** @var User $user */
        $user = $request->user();
        $new = $action->handle($user, $qrCode);

        return redirect()->route('qr.index')->with('success', "Kod QR \"{$new->title}\" został utworzony.");
    }

    public function export(ExportQrRequest $request, QrRenderer $renderer): HttpResponse
    {
        $data = $request->string('data')->toString();
        $ecc = $request->string('ecc')->toString();
        $format = $request->string('format')->toString();

        return match ($format) {
            'png' => response($renderer->png($data, $ecc), 200, [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'attachment; filename="qrcode.png"',
            ]),
            'svg' => response($renderer->svg($data, $ecc, true), 200, [
                'Content-Type' => 'image/svg+xml',
                'Content-Disposition' => 'attachment; filename="qrcode.svg"',
            ]),
            'eps' => response($renderer->eps($data, $ecc), 200, [
                'Content-Type' => 'application/postscript',
                'Content-Disposition' => 'attachment; filename="qrcode.eps"',
            ]),
            'pdf' => response($renderer->pdf($data, $ecc), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="qrcode.pdf"',
            ]),
            default => abort(422),
        };
    }
}
