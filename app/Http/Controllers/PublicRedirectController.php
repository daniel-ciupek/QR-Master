<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\RecordScanJob;
use App\Models\QrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
    public function __invoke(Request $request, string $hash): RedirectResponse
    {
        $qrCode = QrCode::active()->where('short_hash', $hash)->first();

        if ($qrCode === null || $qrCode->destination_url === null) {
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
}
