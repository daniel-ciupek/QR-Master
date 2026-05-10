<?php

declare(strict_types=1);

use App\Actions\QrCode\CreateQrCodeAction;
use App\Actions\QrCode\DeleteQrCodeAction;
use App\Actions\QrCode\UpdateQrCodeAction;
use App\Data\QrCodeData;
use App\Enums\QrCodeType;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function makeData(array $overrides = []): QrCodeData
{
    return new QrCodeData(
        title: $overrides['title'] ?? 'My QR Code',
        type: $overrides['type'] ?? QrCodeType::Url,
        destination_url: $overrides['destination_url'] ?? 'https://example.com',
        settings: $overrides['settings'] ?? [],
        is_active: $overrides['is_active'] ?? true,
        expires_at: $overrides['expires_at'] ?? null,
        password: $overrides['password'] ?? null,
    );
}

// ── CreateQrCodeAction ───────────────────────────────────────────────

it('creates a qr code with correct attributes', function (): void {
    $user = User::factory()->create();
    $action = app(CreateQrCodeAction::class);

    $qr = $action->handle($user, makeData());

    expect($qr)->toBeInstanceOf(QrCode::class)
        ->and($qr->user_id)->toBe($user->id)
        ->and($qr->title)->toBe('My QR Code')
        ->and($qr->type)->toBe(QrCodeType::Url)
        ->and($qr->destination_url)->toBe('https://example.com')
        ->and($qr->short_hash)->toHaveLength(8)
        ->and($qr->is_active)->toBeTrue()
        ->and($qr->password_hash)->toBeNull();
});

it('hashes password when provided on create', function (): void {
    $user = User::factory()->create();
    $qr = app(CreateQrCodeAction::class)->handle($user, makeData(['password' => 'secret123']));

    expect($qr->password_hash)->not->toBeNull()
        ->and(Hash::check('secret123', $qr->password_hash))->toBeTrue();
});

it('generates unique short_hash per code', function (): void {
    $user = User::factory()->create();
    $action = app(CreateQrCodeAction::class);

    $hashes = collect(range(1, 10))->map(fn () => $action->handle($user, makeData())->short_hash);

    expect($hashes->unique())->toHaveCount(10);
});

// ── UpdateQrCodeAction ───────────────────────────────────────────────

it('updates qr code attributes', function (): void {
    $user = User::factory()->create();
    $qr = app(CreateQrCodeAction::class)->handle($user, makeData());

    $updated = app(UpdateQrCodeAction::class)->handle($qr, makeData([
        'title' => 'Updated Title',
        'destination_url' => 'https://new-url.com',
        'is_active' => false,
    ]));

    expect($updated->title)->toBe('Updated Title')
        ->and($updated->destination_url)->toBe('https://new-url.com')
        ->and($updated->is_active)->toBeFalse()
        ->and($updated->short_hash)->toBe($qr->short_hash);
});

it('does not change password_hash when password is null on update', function (): void {
    $user = User::factory()->create();
    $qr = app(CreateQrCodeAction::class)->handle($user, makeData(['password' => 'old']));
    $oldHash = $qr->password_hash;

    app(UpdateQrCodeAction::class)->handle($qr, makeData(['password' => null]));

    expect($qr->fresh()->password_hash)->toBe($oldHash);
});

// ── DeleteQrCodeAction ───────────────────────────────────────────────

it('soft-deletes a qr code', function (): void {
    $user = User::factory()->create();
    $qr = app(CreateQrCodeAction::class)->handle($user, makeData());

    app(DeleteQrCodeAction::class)->handle($qr);

    expect(QrCode::find($qr->id))->toBeNull()
        ->and(QrCode::withTrashed()->find($qr->id))->not->toBeNull();
});
