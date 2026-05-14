<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrCode;

use App\Actions\BioLink\CreateBioLinkAction;
use App\Actions\QrCode\BulkAttachTagAction;
use App\Actions\QrCode\BulkDeleteQrCodesAction;
use App\Actions\QrCode\BulkToggleActiveQrCodesAction;
use App\Actions\QrCode\CreateQrCodeAction;
use App\Actions\QrCode\DeleteQrCodeAction;
use App\Actions\QrCode\DuplicateQrCodeAction;
use App\Actions\QrCode\SyncQrCodeTagsAction;
use App\Actions\QrCode\ToggleActiveQrCodeAction;
use App\Actions\QrCode\UpdateQrCodeAction;
use App\Actions\QrCode\UploadPdfMenuAction;
use App\Actions\QrCode\UploadQrLogoAction;
use App\Data\QrCodeData;
use App\Enums\QrCodeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\QrCode\BulkQrRequest;
use App\Http\Requests\QrCode\ExportQrRequest;
use App\Http\Requests\QrCode\StoreQrCodeRequest;
use App\Http\Requests\QrCode\UpdateQrCodeRequest;
use App\Http\Requests\QrCode\UploadQrLogoRequest;
use App\Models\QrCode;
use App\Models\QrUserTemplate;
use App\Models\Tag;
use App\Models\User;
use App\Services\QrContent\QrContentBuilderService;
use App\Services\QrRendering\QrRenderer;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $userTemplates = QrUserTemplate::where('user_id', $user->id)
            ->orderBy('name')
            ->get()
            ->map(fn (QrUserTemplate $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'settings' => $t->settings,
            ])
            ->all();

        return Inertia::render('QrCode/Create', ['userTemplates' => $userTemplates]);
    }

    public function store(
        StoreQrCodeRequest $request,
        CreateQrCodeAction $action,
        QrContentBuilderService $builder,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $type = QrCodeType::from($validated['type']);

        $destinationUrl = $builder->build($type, $validated);

        $settings = array_merge(
            $validated['settings'] ?? [],
            $this->extractTypeSettings($type, $validated),
        );

        $data = new QrCodeData(
            title: $validated['title'],
            type: $type,
            destination_url: $destinationUrl !== '' ? $destinationUrl : null,
            settings: $settings,
            is_active: (bool) ($validated['is_active'] ?? true),
            expires_at: isset($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null,
            activates_at: isset($validated['activates_at']) ? Carbon::parse($validated['activates_at']) : null,
            geo_allowed_countries: ! empty($validated['geo_allowed_countries'])
                ? array_map('strtoupper', (array) $validated['geo_allowed_countries'])
                : null,
        );

        $bioLink = null;

        $qrCode = DB::transaction(function () use ($user, $data, $validated, $action, &$bioLink): QrCode {
            $qrCode = $action->handle($user, $data);

            if ($data->type === QrCodeType::Pdf) {
                // destination_url = self-referential viewer URL; must be set after short_hash is generated
                $qrCode->destination_url = route('qr.redirect', $qrCode->short_hash);
            }

            if ($data->type === QrCodeType::BioLink) {
                $bioLink = app(CreateBioLinkAction::class)->handle($user, $qrCode);
                $qrCode->destination_url = route('bio-link.show', $bioLink->slug);
            }

            if ($data->type === QrCodeType::App) {
                $qrCode->destination_url = route('qr.redirect', $qrCode->short_hash);
            }

            $qrCode->vcard_phone = $validated['vcard_phone'] ?? null;
            $qrCode->vcard_email = $validated['vcard_email'] ?? null;
            $qrCode->wifi_password = $validated['wifi_password'] ?? null;
            $qrCode->save();

            return $qrCode;
        });

        // PDF upload outside transaction — medialibrary manages its own DB state
        if ($type === QrCodeType::Pdf && $request->hasFile('pdf_file')) {
            $qrCode->addMediaFromRequest('pdf_file')
                ->toMediaCollection('pdf_menu');
        }

        // Bio-Link: redirect to editor immediately after creation
        if ($type === QrCodeType::BioLink && $bioLink !== null) {
            return redirect()->route('bio-link.edit', $bioLink->id)
                ->with('success', 'Bio-Link został utworzony.');
        }

        return redirect()->route('qr.index')->with('success', 'Kod QR został utworzony.');
    }

    /**
     * Extract non-sensitive type-specific fields for the settings JSON.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function extractTypeSettings(QrCodeType $type, array $validated): array
    {
        return match ($type) {
            QrCodeType::VCard => array_filter([
                'vcard_first_name' => $validated['vcard_first_name'] ?? null,
                'vcard_last_name' => $validated['vcard_last_name'] ?? null,
                'vcard_company' => $validated['vcard_company'] ?? null,
                'vcard_job_title' => $validated['vcard_job_title'] ?? null,
                'vcard_website' => $validated['vcard_website'] ?? null,
                'vcard_address' => $validated['vcard_address'] ?? null,
                'vcard_photo_url' => $validated['vcard_photo_url'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
            QrCodeType::Wifi => array_filter([
                'wifi_ssid' => $validated['wifi_ssid'] ?? null,
                'wifi_security' => $validated['wifi_security'] ?? null,
                'wifi_hidden' => isset($validated['wifi_hidden']) ? (bool) $validated['wifi_hidden'] : null,
            ], fn ($v) => $v !== null && $v !== ''),
            QrCodeType::Email => array_filter([
                'email_address' => $validated['email_address'] ?? null,
                'email_subject' => $validated['email_subject'] ?? null,
                'email_body' => $validated['email_body'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
            QrCodeType::Sms => array_filter([
                'sms_number' => $validated['sms_number'] ?? null,
                'sms_message' => $validated['sms_message'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
            QrCodeType::Phone => array_filter([
                'phone_number' => $validated['phone_number'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
            QrCodeType::Geo => array_filter([
                'geo_lat' => $validated['geo_lat'] ?? null,
                'geo_lng' => $validated['geo_lng'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
            QrCodeType::App => array_filter([
                'app_ios_url' => $validated['app_ios_url'] ?? null,
                'app_android_url' => $validated['app_android_url'] ?? null,
                'app_fallback_url' => $validated['app_fallback_url'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
            QrCodeType::Calendar => array_filter([
                'calendar_title' => $validated['calendar_title'] ?? null,
                'calendar_start' => $validated['calendar_start'] ?? null,
                'calendar_end' => $validated['calendar_end'] ?? null,
                'calendar_description' => $validated['calendar_description'] ?? null,
                'calendar_location' => $validated['calendar_location'] ?? null,
                'calendar_all_day' => ($validated['calendar_all_day'] ?? null) ? true : null,
            ], fn ($v) => $v !== null && $v !== ''),
            QrCodeType::Review => array_filter([
                'review_platform' => $validated['review_platform'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
            QrCodeType::Crypto => array_filter([
                'crypto_coin' => $validated['crypto_coin'] ?? null,
                'crypto_address' => $validated['crypto_address'] ?? null,
                'crypto_amount' => isset($validated['crypto_amount']) && $validated['crypto_amount'] !== '' ? (string) $validated['crypto_amount'] : null,
                'crypto_label' => $validated['crypto_label'] ?? null,
                'crypto_message' => $validated['crypto_message'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
            default => [],
        };
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
                'activates_at' => $qrCode->activates_at?->toDateString(),
                'geo_allowed_countries' => $qrCode->geo_allowed_countries ?? [],
                'tag_ids' => $qrCode->tags->pluck('id')->toArray(),
                'logo_url' => $qrCode->getFirstMediaUrl('logo') ?: null,
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
            activates_at: $request->filled('activates_at')
                ? Carbon::parse($request->input('activates_at'))
                : null,
            geo_allowed_countries: ! empty($request->array('geo_allowed_countries'))
                ? array_map('strtoupper', $request->array('geo_allowed_countries'))
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

    public function uploadLogo(UploadQrLogoRequest $request, QrCode $qrCode, UploadQrLogoAction $action): RedirectResponse
    {
        Gate::authorize('update', $qrCode);
        $action->handle($qrCode, $request->file('logo'));

        return back();
    }

    public function deleteLogo(QrCode $qrCode): RedirectResponse
    {
        Gate::authorize('update', $qrCode);
        $qrCode->clearMediaCollection('logo');

        return back();
    }

    public function uploadPdf(Request $request, QrCode $qrCode, UploadPdfMenuAction $action): RedirectResponse
    {
        Gate::authorize('update', $qrCode);
        $request->validate(['pdf_file' => ['required', 'file', 'mimes:pdf', 'max:10240']]);

        $action->handle($qrCode, $request->file('pdf_file'));

        return back()->with('success', 'Plik PDF został zaktualizowany.');
    }

    public function bulkDestroy(BulkQrRequest $request, BulkDeleteQrCodesAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $count = $action->handle($user, $request->array('ids'));

        return redirect()->route('qr.index')->with('success', "Usunięto {$count} kodów QR.");
    }

    public function bulkPause(BulkQrRequest $request, BulkToggleActiveQrCodesAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->handle($user, $request->array('ids'), false);

        return back();
    }

    public function bulkActivate(BulkQrRequest $request, BulkToggleActiveQrCodesAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->handle($user, $request->array('ids'), true);

        return back();
    }

    public function bulkTag(BulkQrRequest $request, BulkAttachTagAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tagId = (int) $request->input('tag_id');
        $action->handle($user, $request->array('ids'), $tagId);

        return back();
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
