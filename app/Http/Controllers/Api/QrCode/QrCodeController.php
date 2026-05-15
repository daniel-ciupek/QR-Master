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

final class QrCodeController extends Controller
{
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

    public function show(Request $request, QrCode $qrCode): QrCodeResource
    {
        abort_unless((bool) $request->user()?->tokenCan('qrcodes:read'), 403, 'Missing ability: qrcodes:read');

        /** @var User $user */
        $user = $request->user();
        abort_unless($user->id === $qrCode->user_id, 403);

        $qrCode->load('tags');

        return new QrCodeResource($qrCode);
    }

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

        if (array_key_exists('tag_ids', $validated)) {
            $syncTags->handle($qrCode, $user, array_map('intval', $validated['tag_ids'] ?? []));
        }

        $qrCode->load('tags');

        return new QrCodeResource($qrCode);
    }

    public function destroy(Request $request, QrCode $qrCode, DeleteQrCodeAction $action): JsonResponse
    {
        abort_unless((bool) $request->user()?->tokenCan('qrcodes:write'), 403, 'Missing ability: qrcodes:write');
        Gate::authorize('delete', $qrCode);

        $action->handle($qrCode);

        return response()->json(null, 204);
    }

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
