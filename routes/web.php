<?php

declare(strict_types=1);

use App\Http\Controllers\Profile\PasskeysController;
use App\Http\Controllers\Profile\SecurityController;
use App\Http\Controllers\Profile\SessionsController;
use App\Http\Controllers\WebAuthn\WebAuthnLoginController;
use App\Http\Controllers\WebAuthn\WebAuthnRegisterController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/profile/security', SecurityController::class)->name('profile.security');
    Route::get('/profile/passkeys', PasskeysController::class)->name('profile.passkeys');

    Route::get('/profile/sessions', [SessionsController::class, 'index'])->name('profile.sessions');
    Route::delete('/profile/sessions/others', [SessionsController::class, 'destroyOthers'])->name('profile.sessions.destroyOthers');
    Route::delete('/profile/sessions/{userSession}', [SessionsController::class, 'destroy'])->name('profile.sessions.destroy');

    // WebAuthn passkey registration (wymaga auth)
    Route::prefix('webauthn')->name('webauthn.')->group(function () {
        Route::post('register/options', [WebAuthnRegisterController::class, 'options'])->name('register.options');
        Route::post('register', [WebAuthnRegisterController::class, 'register'])->name('register');
    });
});

// WebAuthn login (bez auth — publiczny endpoint)
Route::prefix('webauthn')->name('webauthn.')->group(function () {
    Route::post('login/options', [WebAuthnLoginController::class, 'options'])->name('login.options');
    Route::post('login', [WebAuthnLoginController::class, 'login'])->name('login');
});
