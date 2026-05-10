<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrCode;

use App\Http\Controllers\Controller;
use App\Http\Requests\QrCode\ExportQrRequest;
use App\Services\QrRendering\QrRenderer;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class QrCodeController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('QrCode/Create');
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
