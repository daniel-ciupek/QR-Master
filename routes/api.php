<?php

declare(strict_types=1);

use App\Http\Controllers\Api\QrCode\BulkQrCodeController;
use App\Http\Controllers\Api\QrCode\QrCodeController;
use App\Http\Controllers\Api\Tag\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
| Auth: Bearer token (Laravel Sanctum)
| All routes require sanctum auth + plan.feature:api (Business+ plan)
|
*/

Route::prefix('v1')->name('api.v1.')->middleware(['auth:sanctum', 'plan.feature:api', 'throttle:api'])->group(function () {
    // Bulk operations — must be before apiResource to avoid route conflicts
    Route::post('qrcodes/bulk', [BulkQrCodeController::class, 'store'])->name('qrcodes.bulk.store');
    Route::get('qrcodes/bulk/{batchId}', [BulkQrCodeController::class, 'status'])->name('qrcodes.bulk.status');

    // QR Codes — CRUD
    Route::apiResource('qrcodes', QrCodeController::class);

    // QR Code analytics (token ability: analytics:read)
    Route::get('qrcodes/{qrCode}/stats', [QrCodeController::class, 'stats'])->name('qrcodes.stats');
    Route::get('qrcodes/{qrCode}/scans', [QrCodeController::class, 'scans'])->name('qrcodes.scans');

    // Folders (Tags)
    Route::get('folders', [TagController::class, 'index'])->name('folders.index');
    Route::post('folders', [TagController::class, 'store'])->name('folders.store');
});
