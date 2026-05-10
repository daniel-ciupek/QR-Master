<?php

declare(strict_types=1);

use App\Enums\QrCodeType;
use App\Jobs\RecordScanJob;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function makeQrCode(array $overrides = []): QrCode
{
    $user = User::factory()->create();

    return QrCode::create(array_merge([
        'user_id' => $user->id,
        'type' => QrCodeType::Url,
        'title' => 'Test QR',
        'short_hash' => 'test1234',
        'slug' => 'test-qr',
        'destination_url' => 'https://example.com',
        'settings' => [],
        'is_active' => true,
    ], $overrides));
}

it('redirects to destination_url for active code', function (): void {
    Queue::fake();
    makeQrCode();

    $response = $this->get('/q/test1234');

    $response->assertRedirect('https://example.com');
    Queue::assertPushed(RecordScanJob::class);
});

it('sets security headers on redirect', function (): void {
    Queue::fake();
    makeQrCode();

    $response = $this->get('/q/test1234');

    // Laravel merges Cache-Control — verify critical directives are present
    $cacheControl = $response->headers->get('Cache-Control', '');
    expect($cacheControl)
        ->toContain('no-store')
        ->toContain('no-cache');
    $response->assertHeader('X-Robots-Tag', 'noindex');
});

it('returns 404 for unknown hash', function (): void {
    $this->get('/q/unknownx')->assertNotFound();
});

it('returns 404 for inactive code', function (): void {
    makeQrCode(['is_active' => false, 'short_hash' => 'inactiv1']);

    $this->get('/q/inactiv1')->assertNotFound();
});

it('returns 404 for expired code', function (): void {
    makeQrCode([
        'short_hash' => 'expird12',
        'expires_at' => now()->subDay(),
    ]);

    $this->get('/q/expird12')->assertNotFound();
});

it('returns 404 when destination_url is null', function (): void {
    makeQrCode([
        'short_hash' => 'nourlab1',
        'destination_url' => null,
    ]);

    $this->get('/q/nourlab1')->assertNotFound();
});

it('returns 403 for password-protected code', function (): void {
    makeQrCode([
        'short_hash' => 'passwd12',
        'password_hash' => bcrypt('secret'),
    ]);

    $this->get('/q/passwd12')->assertForbidden();
});

it('dispatches RecordScanJob with hashed IP on successful redirect', function (): void {
    Queue::fake();
    makeQrCode();

    $this->get('/q/test1234');

    Queue::assertPushed(RecordScanJob::class, function (RecordScanJob $job) {
        return $job->qrCodeId === QrCode::where('short_hash', 'test1234')->first()->id
            && strlen($job->ipHash) === 64; // SHA-256 hex
    });
});

it('does not redirect soft-deleted code', function (): void {
    $qr = makeQrCode(['short_hash' => 'deleted1']);
    $qr->delete();

    $this->get('/q/deleted1')->assertNotFound();
});
