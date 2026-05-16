<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\QrCode;

use App\Actions\QrCode\CreateQrCodeAction;
use App\Actions\QrCode\DeleteQrCodeAction;
use App\Actions\QrCode\SyncQrCodeTagsAction;
use App\Actions\QrCode\UpdateQrCodeAction;
use App\Data\QrCodeData;
use App\Enums\QrCodeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreQrCodeApiRequest;
use App\Http\Requests\Api\UpdateQrCodeApiRequest;
use App\Http\Resources\Api\QrCodeResource;
use App\Http\Resources\Api\ScanLogResource;
use App\Models\QrCode;
use App\Models\User;
use App\Services\QrContent\QrContentBuilderService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * @group QR Codes
 *
 * Create, read, update, delete and analyse QR codes programmatically.
 * All endpoints require the `qrcodes:read` or `qrcodes:write` token ability.
 */
final class QrCodeController extends Controller
{
    /**
     * List QR codes
     *
     * Returns a paginated list (15 per page) of the authenticated user's QR codes.
     *
     * @queryParam type string Filter by QR type. Example: url
     * @queryParam is_active boolean Filter by active status. Example: true
     * @queryParam tag_id integer Filter by folder (tag) ID. Example: 1
     * @queryParam search string Search by title (case-insensitive). Example: Campaign
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "data": [{"id": 1, "type": "url", "title": "My QR", "short_hash": "abc123", "destination_url": "https://example.com", "is_active": true, "scan_count": 42, "redirect_url": "https://app.qr-master.app/q/abc123", "created_at": "2026-05-15T10:00:00+00:00"}],
     *   "meta": {"current_page": 1, "last_page": 3, "per_page": 15, "total": 42},
     *   "links": {"first": "...", "last": "...", "prev": null, "next": "..."}
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless((bool) $request->user()?->tokenCan('qrcodes:read'), 403, 'Missing ability: qrcodes:read');

        /** @var User $user */
        $user = $request->user();

        $qrCodes = QrCode::forUser($user->id)
            ->with('tags')
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->input('tag_id'), fn ($q, $tagId) => $q->whereHas('tags', fn ($t) => $t->where('tags.id', (int) $tagId)))
            ->when($request->input('search'), fn ($q, $s) => $q->where('title', 'ilike', "%{$s}%"))
            ->latest()
            ->paginate(15);

        return QrCodeResource::collection($qrCodes);
    }

    /**
     * Create QR code
     *
     * Creates a new dynamic QR code. PDF and bio-link types are not supported via API.
     * Requires the `qrcodes:write` token ability.
     *
     * @bodyParam title string required Human-readable name. Example: Summer Campaign
     * @bodyParam type string required QR type (url|text|email|phone|sms|vcard|wifi|geo|app|calendar|crypto|review). Example: url
     * @bodyParam destination_url string URL for `type=url`. Example: https://example.com
     * @bodyParam is_active boolean Whether the QR is active. Example: true
     * @bodyParam expires_at string|null ISO date for expiry. Example: 2026-12-31
     * @bodyParam scan_limit integer|null Maximum number of scans. Example: 1000
     * @bodyParam tag_ids integer[] Folder IDs to assign. Example: [1, 2]
     * @bodyParam settings object Visual settings (dot style, colors, logo, etc.).
     *
     * @response 201 {"data": {"id": 1, "type": "url", "title": "Summer Campaign", "short_hash": "abc123", "destination_url": "https://example.com", "is_active": true, "scan_count": 0, "redirect_url": "https://app.qr-master.app/q/abc123", "created_at": "2026-05-15T10:00:00+00:00"}}
     * @response 403 {"message": "Missing ability: qrcodes:write"}
     * @response 422 {"message": "The title field is required.", "errors": {"title": ["The title field is required."]}}
     */
    public function store(
        StoreQrCodeApiRequest $request,
        CreateQrCodeAction $action,
        SyncQrCodeTagsAction $syncTags,
        QrContentBuilderService $builder,
    ): JsonResponse {
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
            fallback_url: $validated['fallback_url'] ?? null,
            settings: $settings,
            is_active: (bool) ($validated['is_active'] ?? true),
            expires_at: isset($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null,
            activates_at: isset($validated['activates_at']) ? Carbon::parse($validated['activates_at']) : null,
            geo_allowed_countries: ! empty($validated['geo_allowed_countries'])
                ? array_map('strtoupper', (array) $validated['geo_allowed_countries'])
                : null,
            scan_limit: isset($validated['scan_limit']) ? (int) $validated['scan_limit'] : null,
        );

        $qrCode = DB::transaction(function () use ($user, $data, $validated, $action): QrCode {
            $qrCode = $action->handle($user, $data);

            if ($data->type === QrCodeType::App) {
                $qrCode->destination_url = route('qr.redirect', $qrCode->short_hash);
                $qrCode->save();
            }

            $qrCode->vcard_phone = $validated['vcard_phone'] ?? null;
            $qrCode->vcard_email = $validated['vcard_email'] ?? null;
            $qrCode->wifi_password = $validated['wifi_password'] ?? null;
            $qrCode->save();

            return $qrCode;
        });

        if (! empty($validated['tag_ids'])) {
            $syncTags->handle($qrCode, $user, array_map('intval', $validated['tag_ids']));
        }

        $qrCode->load('tags');

        return (new QrCodeResource($qrCode))->response()->setStatusCode(201);
    }

    /**
     * Get QR code
     *
     * Returns a single QR code by ID. Requires `qrcodes:read` ability.
     *
     * @urlParam qrCode integer required The QR code ID. Example: 1
     *
     * @response 200 {"data": {"id": 1, "type": "url", "title": "My QR", "short_hash": "abc123", "destination_url": "https://example.com", "is_active": true, "scan_count": 42, "redirect_url": "https://app.qr-master.app/q/abc123", "tags": [], "created_at": "2026-05-15T10:00:00+00:00"}}
     * @response 403 {"message": "Forbidden."}
     * @response 404 {"message": "No query results for model [App\\Models\\QrCode] 999."}
     */
    public function show(Request $request, QrCode $qrCode): QrCodeResource
    {
        abort_unless((bool) $request->user()?->tokenCan('qrcodes:read'), 403, 'Missing ability: qrcodes:read');

        /** @var User $user */
        $user = $request->user();
        abort_unless($user->id === $qrCode->user_id, 403);

        $qrCode->load('tags');

        return new QrCodeResource($qrCode);
    }

    /**
     * Update QR code
     *
     * Updates an existing QR code. Type cannot be changed. Requires `qrcodes:write` ability.
     *
     * @urlParam qrCode integer required The QR code ID. Example: 1
     *
     * @bodyParam title string required New title. Example: Updated Campaign
     * @bodyParam destination_url string New destination URL (for url type). Example: https://new-example.com
     * @bodyParam is_active boolean required Whether the QR is active. Example: true
     * @bodyParam tag_ids integer[] Folder IDs (replaces existing folders). Example: [1]
     *
     * @response 200 {"data": {"id": 1, "type": "url", "title": "Updated Campaign", "short_hash": "abc123", "is_active": true}}
     * @response 403 {"message": "This action is unauthorized."}
     */
    public function update(
        UpdateQrCodeApiRequest $request,
        QrCode $qrCode,
        UpdateQrCodeAction $action,
        SyncQrCodeTagsAction $syncTags,
    ): QrCodeResource {
        Gate::authorize('update', $qrCode);

        $validated = $request->validated();

        /** @var User $user */
        $user = $request->user();

        $data = new QrCodeData(
            title: $validated['title'],
            type: $qrCode->type,
            destination_url: $validated['destination_url'] ?? $qrCode->destination_url,
            fallback_url: $validated['fallback_url'] ?? null,
            settings: $qrCode->settings,
            is_active: (bool) $validated['is_active'],
            expires_at: isset($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null,
            activates_at: isset($validated['activates_at']) ? Carbon::parse($validated['activates_at']) : null,
            geo_allowed_countries: ! empty($validated['geo_allowed_countries'])
                ? array_map('strtoupper', (array) $validated['geo_allowed_countries'])
                : null,
            scan_limit: isset($validated['scan_limit']) ? (int) $validated['scan_limit'] : null,
        );

        $qrCode = $action->handle($qrCode, $data);

        $encryptedFields = array_filter([
            'vcard_phone' => $validated['vcard_phone'] ?? null,
            'vcard_email' => $validated['vcard_email'] ?? null,
            'wifi_password' => $validated['wifi_password'] ?? null,
        ], fn ($v) => $v !== null);

        if ($encryptedFields !== []) {
            $qrCode->fill($encryptedFields)->save();
        }

        if (array_key_exists('tag_ids', $validated)) {
            $syncTags->handle($qrCode, $user, array_map('intval', $validated['tag_ids'] ?? []));
        }

        $qrCode->load('tags');

        return new QrCodeResource($qrCode);
    }

    /**
     * Delete QR code
     *
     * Soft-deletes a QR code. The code will stop redirecting immediately.
     * Hard deletion happens automatically after 30 days. Requires `qrcodes:write` ability.
     *
     * @urlParam qrCode integer required The QR code ID. Example: 1
     *
     * @response 204 {}
     * @response 403 {"message": "This action is unauthorized."}
     */
    public function destroy(Request $request, QrCode $qrCode, DeleteQrCodeAction $action): JsonResponse
    {
        abort_unless((bool) $request->user()?->tokenCan('qrcodes:write'), 403, 'Missing ability: qrcodes:write');
        Gate::authorize('delete', $qrCode);

        $action->handle($qrCode);

        return response()->json(null, 204);
    }

    /**
     * QR code statistics
     *
     * Returns aggregate scan statistics for a QR code. Requires `analytics:read` token ability.
     *
     * @urlParam qrCode integer required The QR code ID. Example: 1
     *
     * @response 200 {"total_scans": 1500, "unique_countries": 12, "top_countries": {"PL": 900, "DE": 300, "GB": 180}, "device_breakdown": {"mobile": 1100, "desktop": 350, "tablet": 50}, "scans_last_7_days": 210, "scans_last_30_days": 840}
     * @response 403 {"message": "Missing ability: analytics:read"}
     */
    public function stats(Request $request, QrCode $qrCode): JsonResponse
    {
        abort_unless((bool) $request->user()?->tokenCan('analytics:read'), 403, 'Missing ability: analytics:read');

        /** @var User $user */
        $user = $request->user();
        abort_unless($user->id === $qrCode->user_id, 403);

        $topCountries = $qrCode->scanLogs()
            ->whereNotNull('country')
            ->selectRaw('country, COUNT(*) as count')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'country');

        $deviceBreakdown = $qrCode->scanLogs()
            ->whereNotNull('device_type')
            ->selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->orderByDesc('count')
            ->pluck('count', 'device_type');

        return response()->json([
            'total_scans' => $qrCode->scan_count,
            'unique_countries' => $qrCode->scanLogs()->whereNotNull('country')->distinct('country')->count('country'),
            'top_countries' => $topCountries,
            'device_breakdown' => $deviceBreakdown,
            'scans_last_7_days' => $qrCode->scanLogs()->where('scanned_at', '>=', now()->subDays(7))->count(),
            'scans_last_30_days' => $qrCode->scanLogs()->where('scanned_at', '>=', now()->subDays(30))->count(),
        ]);
    }

    /**
     * List scan logs
     *
     * Returns paginated scan log entries (50 per page). IP addresses are never returned (GDPR).
     * Requires `analytics:read` token ability.
     *
     * @urlParam qrCode integer required The QR code ID. Example: 1
     *
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {"data": [{"id": 1, "scanned_at": "2026-05-15T10:00:00+00:00", "country": "PL", "city": "Warsaw", "device_type": "mobile", "os": "iOS", "browser": "Safari", "referrer": null}], "meta": {"current_page": 1, "total": 1500}}
     */
    public function scans(Request $request, QrCode $qrCode): AnonymousResourceCollection
    {
        abort_unless((bool) $request->user()?->tokenCan('analytics:read'), 403, 'Missing ability: analytics:read');

        /** @var User $user */
        $user = $request->user();
        abort_unless($user->id === $qrCode->user_id, 403);

        $scans = $qrCode->scanLogs()
            ->orderByDesc('scanned_at')
            ->paginate(50);

        return ScanLogResource::collection($scans);
    }

    /**
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
                'review_url' => $validated['review_url'] ?? null,
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
}
