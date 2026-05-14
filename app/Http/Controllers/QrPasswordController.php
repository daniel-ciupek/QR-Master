<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\QrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

final class QrPasswordController extends Controller
{
    public function show(string $hash): RedirectResponse|Response
    {
        $qrCode = QrCode::active()->where('short_hash', $hash)->first();

        if ($qrCode === null) {
            abort(404);
        }

        if (! $qrCode->isPasswordProtected()) {
            return redirect()->route('qr.redirect', $hash);
        }

        return Inertia::render('QrCode/Password/Prompt', [
            'hash' => $hash,
            'title' => $qrCode->title,
        ]);
    }

    public function verify(Request $request, string $hash): RedirectResponse
    {
        $qrCode = QrCode::active()->where('short_hash', $hash)->first();

        if ($qrCode === null) {
            abort(404);
        }

        if (! $qrCode->isPasswordProtected()) {
            return redirect()->route('qr.redirect', $hash);
        }

        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->string('password')->toString(), $qrCode->password_hash ?? '')) {
            return back()->withErrors(['password' => __('qr.password.wrong')]);
        }

        $request->session()->put("qr_unlocked_{$hash}", true);

        return redirect()->route('qr.redirect', $hash);
    }
}
