<?php

declare(strict_types=1);

use App\Models\QrCode;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Permanently delete QR codes soft-deleted more than 30 days ago
Schedule::command('model:prune', ['--model' => [QrCode::class]])
    ->daily()
    ->withoutOverlapping()
    ->runInBackground();
