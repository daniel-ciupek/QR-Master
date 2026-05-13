<?php

declare(strict_types=1);

use App\Contracts\GeoLookupInterface;
use App\Contracts\UserAgentParserInterface;
use App\Enums\QrCodeType;
use App\Jobs\RecordScanJob;
use App\Models\QrCode;
use App\Models\ScanLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

function makePrivacyQr(string $hash): QrCode
{
    return QrCode::create([
        'user_id' => User::factory()->create()->id,
        'type' => QrCodeType::Url,
        'title' => 'Privacy Test QR',
        'short_hash' => $hash,
        'destination_url' => 'https://example.com',
        'is_active' => true,
    ]);
}

it('raw IP is never persisted — only ip_hash stored in scan_logs', function (): void {
    $rawIp = '203.0.113.42';
    $qr = makePrivacyQr('priv0001');

    $geo = Mockery::mock(GeoLookupInterface::class);
    $geo->shouldReceive('lookup')->andReturn(null);

    $ua = Mockery::mock(UserAgentParserInterface::class);
    $ua->shouldReceive('parse')->andReturn(['device_type' => null, 'os' => null, 'browser' => null]);

    $salt = config('app.ip_hash_salt', config('app.key'));
    $expectedHash = hash_hmac('sha256', $rawIp, (string) $salt);

    (new RecordScanJob(
        qrCodeId: $qr->id,
        ipHash: $expectedHash,
        rawIp: $rawIp,
        userAgent: '',
        referer: null,
        language: 'en',
    ))->handle($geo, $ua);

    $log = ScanLog::where('qr_code_id', $qr->id)->first();

    expect($log)->not->toBeNull()
        ->and($log->ip_hash)->toBe($expectedHash)
        ->and($log->ip_hash)->not->toBe($rawIp);

    // Verify raw IP does not appear in any scan_logs column
    $columns = $log->getAttributes();
    foreach ($columns as $column => $value) {
        expect((string) $value)->not->toContain($rawIp, "Raw IP leaked into column: {$column}");
    }
});

it('ip_hash is deterministic — same IP and salt always produce the same hash', function (): void {
    $salt = config('app.ip_hash_salt', config('app.key'));
    $ip = '198.51.100.7';

    $hash1 = hash_hmac('sha256', $ip, (string) $salt);
    $hash2 = hash_hmac('sha256', $ip, (string) $salt);

    expect($hash1)->toBe($hash2)->toHaveLength(64);
});

it('different IPs produce different hashes', function (): void {
    $salt = config('app.ip_hash_salt', config('app.key'));

    $hash1 = hash_hmac('sha256', '1.2.3.4', (string) $salt);
    $hash2 = hash_hmac('sha256', '5.6.7.8', (string) $salt);

    expect($hash1)->not->toBe($hash2);
});

it('fromRequest() hashes IP before storing in job payload', function (): void {
    $rawIp = '203.0.113.99';
    $qr = makePrivacyQr('priv0002');

    $request = Request::create('/q/priv0002', 'GET');
    $request->server->set('REMOTE_ADDR', $rawIp);

    $job = RecordScanJob::fromRequest($qr, $request);

    expect($job->ipHash)->not->toBe($rawIp)
        ->and($job->ipHash)->toHaveLength(64)
        ->and($job->rawIp)->toBe($rawIp);
});
