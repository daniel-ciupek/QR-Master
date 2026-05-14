<?php

declare(strict_types=1);

use App\Enums\QrCodeType;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(PreventRequestForgery::class);
});

function makeProtectedQr(string $hash, string $password): QrCode
{
    $user = User::factory()->create();

    return QrCode::create([
        'user_id' => $user->id,
        'type' => QrCodeType::Url,
        'title' => 'Protected QR',
        'short_hash' => $hash,
        'slug' => "slug-{$hash}",
        'destination_url' => 'https://secret.example.com',
        'settings' => [],
        'is_active' => true,
        'password_hash' => Hash::make($password),
    ]);
}

it('shows password prompt for protected code', function (): void {
    makeProtectedQr('prot1234', 'correctpass');

    $this->get('/q/prot1234/unlock')->assertOk()->assertInertia(
        fn ($page) => $page->component('QrCode/Password/Prompt')
            ->where('hash', 'prot1234')
            ->where('title', 'Protected QR')
    );
});

it('returns 404 on unlock page for unknown hash', function (): void {
    $this->get('/q/unknown1/unlock')->assertNotFound();
});

it('verifies correct password and sets session token', function (): void {
    makeProtectedQr('prot5678', 'mypassword');

    $this->post('/q/prot5678/unlock', ['password' => 'mypassword'])
        ->assertRedirect('/q/prot5678')
        ->assertSessionHas('qr_unlocked_prot5678', true);
});

it('rejects wrong password with validation error', function (): void {
    makeProtectedQr('prot9999', 'rightpass');

    $this->post('/q/prot9999/unlock', ['password' => 'wrongpass'])
        ->assertRedirect()
        ->assertSessionHasErrors('password');
});

it('follows redirect after unlock with session token', function (): void {
    makeProtectedQr('prot0001', 'unlockme');

    $this->withSession(['qr_unlocked_prot0001' => true])
        ->get('/q/prot0001')
        ->assertRedirect('https://secret.example.com');
});
