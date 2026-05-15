<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
| Auth: Bearer token (Laravel Sanctum)
| All routes require sanctum auth + plan.feature:api (Business+ plan)
|
*/

Route::prefix('v1')->name('api.v1.')->middleware(['auth:sanctum', 'plan.feature:api'])->group(function () {
    // QR Codes — 9.3
    // Bulk operations — 9.5
    // Scans — 9.3
});
