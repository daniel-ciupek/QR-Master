<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrCode;

use App\Http\Controllers\Controller;
use App\Http\Requests\QrCode\ExportQrRequest;
use App\Models\QrCode;
use App\Models\User;
use App\Services\QrRendering\QrRenderer;
use Illuminate\Http\Request;
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

        $allowed = ['title', 'type', 'is_active', 'created_at', 'expires_at'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'created_at';
        }
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        $qrCodes = QrCode::forUser($user->id)
            ->when($search !== '', fn ($q) => $q->where('title', 'ilike', "%{$search}%"))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (QrCode $qr) => [
                'id' => $qr->id,
                'title' => $qr->title,
                'type' => $qr->type->value,
                'short_hash' => $qr->short_hash,
                'is_active' => $qr->is_active,
                'is_expired' => $qr->isExpired(),
                'expires_at' => $qr->expires_at?->toDateString(),
                'created_at' => $qr->created_at->toDateString(),
            ]);

        return Inertia::render('QrCode/Index', [
            'qrCodes' => $qrCodes,
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
        ]);
    }

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
