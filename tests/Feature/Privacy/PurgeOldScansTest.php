<?php

declare(strict_types=1);

use App\Enums\QrCodeType;
use App\Models\QrCode;
use App\Models\ScanLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeScanLog(array $overrides = []): ScanLog
{
    $qr = QrCode::create([
        'user_id' => User::factory()->create()->id,
        'type' => QrCodeType::Url,
        'title' => 'Purge Test',
        'short_hash' => substr(uniqid(), -9),
        'destination_url' => 'https://example.com',
        'is_active' => true,
    ]);

    return ScanLog::create(array_merge([
        'qr_code_id' => $qr->id,
        'ip_hash' => str_repeat('f', 64),
        'country' => 'PL',
        'region' => 'Masovian',
        'city' => 'Warsaw',
        'lat' => 52.2297,
        'lng' => 21.0122,
        'scanned_at' => now(),
    ], $overrides));
}

it('purges geo data from scan_logs older than 90 days', function (): void {
    $old = makeScanLog(['scanned_at' => now()->subDays(91)]);
    $fresh = makeScanLog(['scanned_at' => now()->subDays(10)]);

    $this->artisan('privacy:purge-old-scans')->assertSuccessful();

    expect($old->fresh()->country)->toBeNull()
        ->and($old->fresh()->city)->toBeNull()
        ->and($old->fresh()->lat)->toBeNull()
        ->and($old->fresh()->lng)->toBeNull();

    expect($fresh->fresh()->country)->toBe('PL')
        ->and($fresh->fresh()->city)->toBe('Warsaw');
});

it('does not purge scans exactly at the retention boundary', function (): void {
    $boundary = makeScanLog(['scanned_at' => now()->subDays(89)]);

    $this->artisan('privacy:purge-old-scans')->assertSuccessful();

    expect($boundary->fresh()->country)->toBe('PL');
});

it('reports number of purged rows', function (): void {
    makeScanLog(['scanned_at' => now()->subDays(100)]);
    makeScanLog(['scanned_at' => now()->subDays(95)]);

    $this->artisan('privacy:purge-old-scans')
        ->expectsOutputToContain('Purged geo data from 2 scan_logs')
        ->assertSuccessful();
});

it('accepts custom --days option', function (): void {
    $log = makeScanLog(['scanned_at' => now()->subDays(31)]);

    $this->artisan('privacy:purge-old-scans', ['--days' => 30])->assertSuccessful();

    expect($log->fresh()->country)->toBeNull();
});

it('skips rows where geo data is already null', function (): void {
    makeScanLog(['scanned_at' => now()->subDays(100), 'country' => null, 'city' => null, 'lat' => null, 'lng' => null]);

    $this->artisan('privacy:purge-old-scans')
        ->expectsOutputToContain('Purged geo data from 0 scan_logs')
        ->assertSuccessful();
});
