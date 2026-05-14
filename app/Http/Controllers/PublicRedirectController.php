<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\QrCodeType;
use App\Jobs\RecordScanJob;
use App\Models\QrCode;
use App\Services\AbTestService;
use App\Services\GeoLookupService;
use App\Services\SmartRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public QR redirect — highest security priority.
 *
 * Security surface:
 *  - Rate limited at 60 req/min/IP ('public-redirect' limiter in AppServiceProvider)
 *  - Only active, non-expired, non-deleted codes are redirected
 *  - Destination URL stored server-side — no open-redirect from user input
 *  - Password-protected codes: redirect to /q/{hash}/unlock prompt (QrPasswordController)
 *  - Scan logging async (queue) — redirect returns immediately
 *  - IP anonymised via HMAC-SHA256 in RecordScanJob — no PII in queue payload
 *  - Cache-Control: no-store prevents proxy caching of stale redirects
 *  - X-Robots-Tag: noindex prevents search engines from indexing redirect targets
 */
final class PublicRedirectController extends Controller
{
    public function __invoke(Request $request, string $hash): RedirectResponse|Response
    {
        $qrCode = QrCode::active()->where('short_hash', $hash)->first();

        if ($qrCode === null) {
            abort(404);
        }

        if ($qrCode->type === QrCodeType::Pdf) {
            dispatch(RecordScanJob::fromRequest($qrCode, $request));

            $pdfUrl = $qrCode->getFirstMediaUrl('pdf_menu');

            return Inertia::render('QrCode/Pdf/View', [
                'pdfUrl' => $pdfUrl !== '' ? $pdfUrl : null,
                'title' => $qrCode->title,
            ]);
        }

        if ($qrCode->type === QrCodeType::App) {
            $url = $this->resolveAppUrl($request, $qrCode->settings ?? []);

            if ($url === null) {
                abort(404);
            }

            dispatch(RecordScanJob::fromRequest($qrCode, $request));

            return redirect()->away($url, 302)->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'X-Robots-Tag' => 'noindex',
            ]);
        }

        if ($qrCode->destination_url === null) {
            abort(404);
        }

        // Password-protected: require session unlock token from QrPasswordController
        if ($qrCode->isPasswordProtected() && ! $request->session()->get("qr_unlocked_{$hash}")) {
            return redirect()->route('qr.password.show', $hash);
        }

        // Geofencing: check allowlist of countries (fail-open — allow if GeoIP unavailable)
        $allowedCountries = $qrCode->geo_allowed_countries;
        if (! empty($allowedCountries)) {
            $geo = app(GeoLookupService::class)->lookup($request->ip() ?? '');
            $country = $geo['country'] ?? null;
            if ($country !== null && ! in_array($country, $allowedCountries, true)) {
                return Inertia::render('QrCode/GeoBlocked', [
                    'title' => $qrCode->title,
                    'allowedCountries' => $allowedCountries,
                ]);
            }
        }

        // Click cap: atomic increment — only succeeds while scan_count < scan_limit
        if ($qrCode->scan_limit !== null) {
            $incremented = DB::table('qr_codes')
                ->where('id', $qrCode->id)
                ->whereColumn('scan_count', '<', 'scan_limit')
                ->increment('scan_count');

            if ($incremented === 0) {
                return Inertia::render('QrCode/CapReached', [
                    'title' => $qrCode->title,
                    'limit' => $qrCode->scan_limit,
                ]);
            }
        }

        // Smart redirect rules take priority over everything
        $smartUrl = app(SmartRedirectService::class)->resolve($qrCode, $request);

        if ($smartUrl !== null) {
            $redirectTo = $smartUrl;
        } elseif ($qrCode->fallback_url !== null) {
            // Fallback URL: used when no smart redirect rule matched
            $redirectTo = $qrCode->fallback_url;
        } else {
            // A/B test: weighted random variant selection (if no smart rule matched)
            $abTest = $qrCode->abTest;
            if ($abTest?->is_active) {
                $variant = app(AbTestService::class)->selectVariant($abTest);
                if ($variant !== null) {
                    app(AbTestService::class)->recordVariantScan($variant, $abTest);
                    $redirectTo = $variant->destination_url;
                } else {
                    $redirectTo = $qrCode->destination_url;
                }
            } else {
                $redirectTo = $qrCode->destination_url;
            }
        }

        dispatch(RecordScanJob::fromRequest($qrCode, $request));

        return redirect()
            ->away($redirectTo, 302)
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'X-Robots-Tag' => 'noindex',
            ]);
    }

    /** @param array<string, mixed> $settings */
    private function resolveAppUrl(Request $request, array $settings): ?string
    {
        $ua = strtolower($request->userAgent() ?? '');

        $isIos = str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ipod');
        $isAndroid = str_contains($ua, 'android');

        $ios = ($settings['app_ios_url'] ?? '') ?: null;
        $android = ($settings['app_android_url'] ?? '') ?: null;
        $fallback = ($settings['app_fallback_url'] ?? '') ?: null;

        if ($isIos) {
            return $ios ?? $fallback ?? $android;
        }

        if ($isAndroid) {
            return $android ?? $fallback ?? $ios;
        }

        return $fallback ?? $ios ?? $android;
    }
}
