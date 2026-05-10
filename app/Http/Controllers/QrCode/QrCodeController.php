<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrCode;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class QrCodeController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('QrCode/Create');
    }
}
