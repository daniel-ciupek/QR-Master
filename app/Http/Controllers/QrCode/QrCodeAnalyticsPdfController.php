<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrCode;

use App\Actions\QrCode\ExportAnalyticsPdfAction;
use App\Http\Controllers\Controller;
use App\Models\QrCode;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class QrCodeAnalyticsPdfController extends Controller
{
    public function __invoke(QrCode $qrCode, ExportAnalyticsPdfAction $action): Response
    {
        Gate::authorize('exportPdf', $qrCode);

        $filename = 'qr-report-'.$qrCode->short_hash.'-'.now()->format('Y-m-d').'.pdf';

        return $action->handle($qrCode)->download($filename);
    }
}
