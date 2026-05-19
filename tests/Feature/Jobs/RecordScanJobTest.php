<?php

declare(strict_types=1);

use App\Contracts\GeoLookupInterface;
use App\Contracts\UserAgentParserInterface;
use App\Enums\QrCodeType;
use App\Jobs\RecordScanJob;
use App\Models\QrCode;
use App\Models\ScanLog;
use App\Models\User;
use App\Services\GeoLookupService;
use App\Services\UserAgentParserService;
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

function makeGeoMock(?array $return = null): GeoLookupInterface
{
    $mock = Mockery::mock(GeoLookupInterface::class);
    $mock->shouldReceive('lookup')->andReturn($return);

    return $mock;
}

function makeUaMock(array $return = ['device_type' => null, 'os' => null, 'browser' => null]): UserAgentParserInterface
{
    $mock = Mockery::mock(UserAgentParserInterface::class);
    $mock->shouldReceive('parse')->andReturn($return);

    return $mock;
}

it('handle() creates a ScanLog with geo data', function (): void {
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

    $job->handle($geoMock, makeUaMock());

    $log = ScanLog::where('qr_code_id', $qr->id)->first();
    expect($log)->not->toBeNull()
        ->and($log->ip_hash)->toBe(str_repeat('a', 64))
        ->and($log->country)->toBe('PL')
        ->and($log->city)->toBe('Warsaw')
        ->and($log->lat)->toBe(52.2297);
});

it('handle() stores null geo fields when lookup returns null', function (): void {
    $qr = makeScanQr('scan0002');

    (new RecordScanJob($qr->id, str_repeat('b', 64), '127.0.0.1', '', null, 'en'))
        ->handle(makeGeoMock(null), makeUaMock());

    $log = ScanLog::where('qr_code_id', $qr->id)->first();
    expect($log)->not->toBeNull()
        ->and($log->country)->toBeNull()
        ->and($log->lat)->toBeNull();
});

it('handle() stores device_type, os and browser from UA parser', function (): void {
    $qr = makeScanQr('scan0003');

    $uaMock = Mockery::mock(UserAgentParserInterface::class);
    $uaMock->shouldReceive('parse')
        ->once()
        ->with('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0)')
        ->andReturn(['device_type' => 'mobile', 'os' => 'iOS', 'browser' => 'Safari']);

    $job = new RecordScanJob(
        qrCodeId: $qr->id,
        ipHash: str_repeat('d', 64),
        rawIp: '5.6.7.8',
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0)',
        referer: null,
        language: 'en',
    );

    $job->handle(makeGeoMock(), $uaMock);

    $log = ScanLog::where('qr_code_id', $qr->id)->first();
    expect($log->device_type)->toBe('mobile')
        ->and($log->os)->toBe('iOS')
        ->and($log->browser)->toBe('Safari');
});

it('handle() stores null ua fields for empty user agent', function (): void {
    $qr = makeScanQr('scan0004');

    (new RecordScanJob($qr->id, str_repeat('e', 64), '9.9.9.9', '', null, 'en'))
        ->handle(makeGeoMock(), makeUaMock());

    $log = ScanLog::where('qr_code_id', $qr->id)->first();
    expect($log->device_type)->toBeNull()
        ->and($log->os)->toBeNull()
        ->and($log->browser)->toBeNull();
});

it('GeoLookupService skips localhost IPs', function (): void {
    $service = app(GeoLookupService::class);
    expect($service->lookup('127.0.0.1'))->toBeNull();
});

it('UserAgentParserService returns nulls for empty string', function (): void {
    $service = new UserAgentParserService;
    $result = $service->parse('');
    expect($result['device_type'])->toBeNull()
        ->and($result['os'])->toBeNull()
        ->and($result['browser'])->toBeNull();
});

it('UserAgentParserService detects mobile device type', function (): void {
    $service = new UserAgentParserService;
    $result = $service->parse('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1');
    expect($result['device_type'])->toBe('mobile');
});

it('job is routed to scans queue', function (): void {
    $job = new RecordScanJob(1, str_repeat('c', 64), '1.2.3.4', 'ua', null, 'en');
    expect($job->queue)->toBe('scans');
});
