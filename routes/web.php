<?php

declare(strict_types=1);

use App\Http\Controllers\Api\SuggestPaletteController;
use App\Http\Controllers\Billing\BillingDashboardController;
use App\Http\Controllers\Billing\BillingSuccessController;
use App\Http\Controllers\Billing\CustomerPortalController;
use App\Http\Controllers\Billing\PricingController;
use App\Http\Controllers\Billing\StripeWebhookController;
use App\Http\Controllers\Billing\SubscribeController;
use App\Http\Controllers\BioLink\BioLinkClickController;
use App\Http\Controllers\BioLink\BioLinkController;
use App\Http\Controllers\BioLink\BioLinkItemController;
use App\Http\Controllers\BioLink\BioLinkPublicController;
use App\Http\Controllers\BotChallengeController;
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
use App\Http\Controllers\QrPasswordController;
use App\Http\Controllers\QrUserTemplateController;
use App\Http\Controllers\Tag\TagController;
use App\Http\Controllers\WebAuthn\WebAuthnLoginController;
use App\Http\Controllers\WebAuthn\WebAuthnRegisterController;
use App\Http\Middleware\CheckBotSuspicion;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

// Pricing — public, optional auth (shows current plan if logged in)
Route::get('/pricing', PricingController::class)->name('pricing')->middleware('web');

// Stripe Webhooks — public, CSRF excluded (verified by Stripe signature)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook')
    ->withoutMiddleware([PreventRequestForgery::class]);

// Public QR redirect — rate limited, bot-suspicion checked
Route::get('/q/{hash}', PublicRedirectController::class)
    ->middleware(['throttle:public-redirect', CheckBotSuspicion::class])
    ->name('qr.redirect');

// Password-protected QR unlock
Route::get('/q/{hash}/unlock', [QrPasswordController::class, 'show'])
    ->middleware('throttle:public-redirect')
    ->name('qr.password.show');
Route::post('/q/{hash}/unlock', [QrPasswordController::class, 'verify'])
    ->middleware('throttle:qr-password')
    ->name('qr.password.verify');

// Anti-bot Turnstile challenge
Route::get('/q/{hash}/challenge', [BotChallengeController::class, 'show'])
    ->middleware('throttle:public-redirect')
    ->name('qr.challenge.show');
Route::post('/q/{hash}/challenge', [BotChallengeController::class, 'verify'])
    ->middleware('throttle:qr-password')
    ->name('qr.challenge.verify');

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
        Route::get('/{qrCode}/ab-test', [AbTestController::class, 'index'])->name('ab-test.index')->middleware('plan.feature:ab-test');
        Route::patch('/{qrCode}/ab-test', [AbTestController::class, 'updateSettings'])->name('ab-test.update')->middleware('plan.feature:ab-test');
        Route::post('/{qrCode}/ab-test/variants', [AbTestController::class, 'storeVariant'])->name('ab-test.variants.store')->middleware('plan.feature:ab-test');
        Route::patch('/{qrCode}/ab-test/variants/{variant}', [AbTestController::class, 'updateVariant'])->name('ab-test.variants.update')->middleware('plan.feature:ab-test');
        Route::delete('/{qrCode}/ab-test/variants/{variant}', [AbTestController::class, 'destroyVariant'])->name('ab-test.variants.destroy')->middleware('plan.feature:ab-test');
        Route::post('/{qrCode}/ab-test/winner', [AbTestController::class, 'selectWinner'])->name('ab-test.winner')->middleware('plan.feature:ab-test');
        Route::get('/{qrCode}/rules', [RedirectRuleController::class, 'index'])->name('rules.index')->middleware('plan.feature:smart-redirect');
        Route::post('/{qrCode}/rules', [RedirectRuleController::class, 'store'])->name('rules.store')->middleware('plan.feature:smart-redirect');
        Route::post('/{qrCode}/rules/reorder', [RedirectRuleController::class, 'reorder'])->name('rules.reorder')->middleware('plan.feature:smart-redirect');
        Route::patch('/{qrCode}/rules/{rule}', [RedirectRuleController::class, 'update'])->name('rules.update')->middleware('plan.feature:smart-redirect');
        Route::delete('/{qrCode}/rules/{rule}', [RedirectRuleController::class, 'destroy'])->name('rules.destroy')->middleware('plan.feature:smart-redirect');
        // Bulk actions — must come before /{qrCode} wildcard
        Route::post('/bulk/delete', [QrCodeController::class, 'bulkDestroy'])->name('bulk.destroy');
        Route::post('/bulk/pause', [QrCodeController::class, 'bulkPause'])->name('bulk.pause');
        Route::post('/bulk/activate', [QrCodeController::class, 'bulkActivate'])->name('bulk.activate');
        Route::post('/bulk/tag', [QrCodeController::class, 'bulkTag'])->name('bulk.tag');
        Route::get('/{qrCode}/analytics', QrCodeAnalyticsController::class)->name('analytics')->middleware('plan.feature:analytics');
        Route::get('/{qrCode}/analytics/export-pdf', QrCodeAnalyticsPdfController::class)->name('analytics.export-pdf')->middleware('plan.feature:analytics');
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

    // Billing — Stripe Checkout + Customer Portal + Dashboard (8.10)
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/subscribe/{plan}', SubscribeController::class)->name('subscribe');
        Route::get('/success', BillingSuccessController::class)->name('success');
        Route::get('/portal', CustomerPortalController::class)->name('portal');
        Route::get('/dashboard', BillingDashboardController::class)->name('dashboard');
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
