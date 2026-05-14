<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\QrCodeType;
use App\Jobs\RecordScanJob;
use App\Models\QrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public QR redirect — highest security priority.
 *
 * Security surface:
 *  - Rate limited at 60 req/min/IP ('public-redirect' limiter in AppServiceProvider)
 *  - Only active, non-expired, non-deleted codes are redirected
 *  - Destination URL stored server-side — no open-redirect from user input
 *  - Password-protected codes: blocked until full prompt UI in Etap 5
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

        // Password-protected: block redirect until Etap 5 implements the prompt UI
        if ($qrCode->isPasswordProtected()) {
            abort(403);
        }

        dispatch(RecordScanJob::fromRequest($qrCode, $request));

        return redirect()
            ->away($qrCode->destination_url, 302)
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
