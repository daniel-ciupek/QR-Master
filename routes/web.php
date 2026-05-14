<?php

declare(strict_types=1);

use App\Http\Controllers\Api\SuggestPaletteController;
use App\Http\Controllers\BioLink\BioLinkClickController;
use App\Http\Controllers\BioLink\BioLinkController;
use App\Http\Controllers\BioLink\BioLinkItemController;
use App\Http\Controllers\BioLink\BioLinkPublicController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\Profile\PasskeysController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Profile\SecurityController;
use App\Http\Controllers\Profile\SessionsController;
use App\Http\Controllers\PublicRedirectController;
use App\Http\Controllers\QrCode\AbTestController;
use App\Http\Controllers\QrCode\QrCodeAnalyticsController;
use App\Http\Controllers\QrCode\QrCodeAnalyticsPdfController;
use App\Http\Controllers\QrCode\QrCodeCompareController;
use App\Http\Controllers\QrCode\QrCodeController;
use App\Http\Controllers\QrCode\RedirectRuleController;
use App\Http\Controllers\QrUserTemplateController;
use App\Http\Controllers\Tag\TagController;
use App\Http\Controllers\WebAuthn\WebAuthnLoginController;
use App\Http\Controllers\WebAuthn\WebAuthnRegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

// Public QR redirect — rate limited, no auth required
Route::get('/q/{hash}', PublicRedirectController::class)
    ->middleware('throttle:public-redirect')
    ->name('qr.redirect');

// Public Bio-Link pages — no auth, rate limited
Route::get('/b/{slug}', BioLinkPublicController::class)
    ->middleware('throttle:public-redirect')
    ->name('bio-link.show');

Route::get('/b/{slug}/link/{item}', BioLinkClickController::class)
    ->middleware('throttle:public-redirect')
    ->name('bio-link.click');

Route::post('/locale', function (Request $request) {
    $locale = in_array($request->input('locale'), ['pl', 'en']) ? $request->input('locale') : 'pl';
    session(['locale' => $locale]);

    return back();
})->name('locale.set');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::prefix('qr')->name('qr.')->group(function () {
        Route::get('/', [QrCodeController::class, 'index'])->name('index');
        Route::get('/compare', QrCodeCompareController::class)->name('compare');
        Route::get('/create', [QrCodeController::class, 'create'])->name('create');
        Route::post('/', [QrCodeController::class, 'store'])->name('store');
        Route::post('/export', [QrCodeController::class, 'export'])->name('export');
        Route::post('/{qrCode}/logo', [QrCodeController::class, 'uploadLogo'])->name('logo.upload');
        Route::delete('/{qrCode}/logo', [QrCodeController::class, 'deleteLogo'])->name('logo.delete');
        Route::post('/{qrCode}/pdf', [QrCodeController::class, 'uploadPdf'])->name('pdf.upload');
        Route::get('/{qrCode}/ab-test', [AbTestController::class, 'index'])->name('ab-test.index');
        Route::patch('/{qrCode}/ab-test', [AbTestController::class, 'updateSettings'])->name('ab-test.update');
        Route::post('/{qrCode}/ab-test/variants', [AbTestController::class, 'storeVariant'])->name('ab-test.variants.store');
        Route::patch('/{qrCode}/ab-test/variants/{variant}', [AbTestController::class, 'updateVariant'])->name('ab-test.variants.update');
        Route::delete('/{qrCode}/ab-test/variants/{variant}', [AbTestController::class, 'destroyVariant'])->name('ab-test.variants.destroy');
        Route::post('/{qrCode}/ab-test/winner', [AbTestController::class, 'selectWinner'])->name('ab-test.winner');
        Route::get('/{qrCode}/rules', [RedirectRuleController::class, 'index'])->name('rules.index');
        Route::post('/{qrCode}/rules', [RedirectRuleController::class, 'store'])->name('rules.store');
        Route::post('/{qrCode}/rules/reorder', [RedirectRuleController::class, 'reorder'])->name('rules.reorder');
        Route::patch('/{qrCode}/rules/{rule}', [RedirectRuleController::class, 'update'])->name('rules.update');
        Route::delete('/{qrCode}/rules/{rule}', [RedirectRuleController::class, 'destroy'])->name('rules.destroy');
        // Bulk actions — must come before /{qrCode} wildcard
        Route::post('/bulk/delete', [QrCodeController::class, 'bulkDestroy'])->name('bulk.destroy');
        Route::post('/bulk/pause', [QrCodeController::class, 'bulkPause'])->name('bulk.pause');
        Route::post('/bulk/activate', [QrCodeController::class, 'bulkActivate'])->name('bulk.activate');
        Route::post('/bulk/tag', [QrCodeController::class, 'bulkTag'])->name('bulk.tag');
        Route::get('/{qrCode}/analytics', QrCodeAnalyticsController::class)->name('analytics');
        Route::get('/{qrCode}/analytics/export-pdf', QrCodeAnalyticsPdfController::class)->name('analytics.export-pdf');
        Route::get('/{qrCode}/edit', [QrCodeController::class, 'edit'])->name('edit');
        Route::patch('/{qrCode}', [QrCodeController::class, 'update'])->name('update');
        Route::delete('/{qrCode}', [QrCodeController::class, 'destroy'])->name('destroy');
        Route::patch('/{qrCode}/toggle-active', [QrCodeController::class, 'toggleActive'])->name('toggleActive');
        Route::post('/{qrCode}/duplicate', [QrCodeController::class, 'duplicate'])->name('duplicate');
    });

    Route::get('/api/ai/suggest-palette', SuggestPaletteController::class)->name('ai.suggest-palette');

    // Bio-Link editor (auth required)
    Route::prefix('bio-links')->name('bio-link.')->group(function () {
        Route::get('/{bioLink}/edit', [BioLinkController::class, 'edit'])->name('edit');
        Route::patch('/{bioLink}', [BioLinkController::class, 'update'])->name('update');
        Route::post('/{bioLink}/avatar', [BioLinkController::class, 'uploadAvatar'])->name('avatar.upload');
        Route::delete('/{bioLink}/avatar', [BioLinkController::class, 'deleteAvatar'])->name('avatar.delete');
        Route::post('/{bioLink}/items', [BioLinkItemController::class, 'store'])->name('items.store');
        Route::post('/{bioLink}/items/reorder', [BioLinkItemController::class, 'reorder'])->name('items.reorder');
        Route::patch('/{bioLink}/items/{item}', [BioLinkItemController::class, 'update'])->name('items.update');
        Route::delete('/{bioLink}/items/{item}', [BioLinkItemController::class, 'destroy'])->name('items.destroy');
    });

    Route::prefix('qr-templates')->name('qr-templates.')->group(function () {
        Route::post('/', [QrUserTemplateController::class, 'store'])->name('store');
        Route::delete('/{qrUserTemplate}', [QrUserTemplateController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('tags')->name('tags.')->group(function () {
        Route::post('/', [TagController::class, 'store'])->name('store');
        Route::delete('/{tag}', [TagController::class, 'destroy'])->name('destroy');
    });

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::get('/profile/export-data', [ProfileController::class, 'exportData'])->name('profile.export');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

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
