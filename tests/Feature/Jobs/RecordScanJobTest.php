<?php

declare(strict_types=1);

use App\Contracts\GeoLookupInterface;
use App\Enums\QrCodeType;
use App\Jobs\RecordScanJob;
use App\Models\QrCode;
use App\Models\ScanLog;
use App\Models\User;
use App\Services\GeoLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeScanQr(string $hash = 'scan0001'): QrCode
{
    $user = User::factory()->create();

    return QrCode::create([
        'user_id' => $user->id,
        'type' => QrCodeType::Url,
        'title' => 'Scan Test QR',
        'short_hash' => $hash,
        'destination_url' => 'https://example.com',
        'is_active' => true,
    ]);
}

it('handle() creates a ScanLog with geo data from GeoLookupService', function (): void {
    $qr = makeScanQr();

    $geoMock = Mockery::mock(GeoLookupInterface::class);
    $geoMock->shouldReceive('lookup')
        ->once()
        ->with('1.2.3.4')
        ->andReturn([
            'country' => 'PL',
            'region' => 'Masovian',
            'city' => 'Warsaw',
            'lat' => 52.2297,
            'lng' => 21.0122,
        ]);

    $job = new RecordScanJob(
        qrCodeId: $qr->id,
        ipHash: str_repeat('a', 64),
        rawIp: '1.2.3.4',
        userAgent: 'Mozilla/5.0',
        referer: 'https://example.com',
        language: 'pl',
    );

    $job->handle($geoMock);

    $log = ScanLog::where('qr_code_id', $qr->id)->first();
    expect($log)->not->toBeNull()
        ->and($log->ip_hash)->toBe(str_repeat('a', 64))
        ->and($log->country)->toBe('PL')
        ->and($log->city)->toBe('Warsaw')
        ->and($log->lat)->toBe(52.2297)
        ->and($log->device_type)->toBeNull();
});

it('handle() stores null geo fields when lookup returns null', function (): void {
    $qr = makeScanQr('scan0002');

    $geoMock = Mockery::mock(GeoLookupInterface::class);
    $geoMock->shouldReceive('lookup')->once()->andReturn(null);

    (new RecordScanJob($qr->id, str_repeat('b', 64), '127.0.0.1', '', null, 'en'))
        ->handle($geoMock);

    $log = ScanLog::where('qr_code_id', $qr->id)->first();
    expect($log)->not->toBeNull()
        ->and($log->country)->toBeNull()
        ->and($log->lat)->toBeNull();
});

it('GeoLookupService skips localhost IPs', function (): void {
    $service = app(GeoLookupService::class);
    expect($service->lookup('127.0.0.1'))->toBeNull();
});

it('job is routed to scans queue', function (): void {
    $job = new RecordScanJob(1, str_repeat('c', 64), '1.2.3.4', 'ua', null, 'en');
    expect($job->queue)->toBe('scans');
});
