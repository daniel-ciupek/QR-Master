<?php

declare(strict_types=1);

use App\Enums\QrCodeType;
use App\Jobs\RecordScanJob;
use App\Models\QrCode;
use App\Models\ScanLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('handle() creates a ScanLog record', function (): void {
    $user = User::factory()->create();
    $qr = QrCode::create([
        'user_id' => $user->id,
        'type' => QrCodeType::Url,
        'title' => 'Test QR',
        'short_hash' => 'tst00001',
        'destination_url' => 'https://example.com',
        'is_active' => true,
    ]);

    $job = new RecordScanJob(
        qrCodeId: $qr->id,
        ipHash: str_repeat('a', 64),
        userAgent: 'Mozilla/5.0',
        referer: 'https://referrer.example.com',
        language: 'pl',
    );

    $job->handle();

    $log = ScanLog::where('qr_code_id', $qr->id)->first();
    expect($log)->not->toBeNull()
        ->and($log->ip_hash)->toBe(str_repeat('a', 64))
        ->and($log->referrer)->toBe('https://referrer.example.com')
        ->and($log->language)->toBe('pl')
        ->and($log->country)->toBeNull()
        ->and($log->device_type)->toBeNull();
});

it('handle() stores null geo/device fields pending 5.5/5.6 implementation', function (): void {
    $user = User::factory()->create();
    $qr = QrCode::create([
        'user_id' => $user->id,
        'type' => QrCodeType::Url,
        'title' => 'Test QR 2',
        'short_hash' => 'tst00002',
        'destination_url' => 'https://example.com',
        'is_active' => true,
    ]);

    (new RecordScanJob($qr->id, str_repeat('b', 64), 'agent', null, 'en'))->handle();

    $log = ScanLog::where('qr_code_id', $qr->id)->first();
    expect($log->country)->toBeNull()
        ->and($log->region)->toBeNull()
        ->and($log->browser)->toBeNull()
        ->and($log->os)->toBeNull();
});

it('job is routed to scans queue', function (): void {
    $job = new RecordScanJob(1, str_repeat('c', 64), 'ua', null, 'en');
    expect($job->queue)->toBe('scans');
});
