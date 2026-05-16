<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CustomDomain;
use App\Models\User;
use App\Services\CustomDomainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class CustomDomainController extends Controller
{
    public function index(Request $request, CustomDomainService $service): Response
    {
        /** @var User $user */
        $user = $request->user();

        $domains = CustomDomain::where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn (CustomDomain $d) => [
                'id' => $d->id,
                'domain' => $d->domain,
                'status' => $d->status,
                'verification_token' => $d->verification_token,
                'verified_at' => $d->verified_at?->toDateString(),
                'ssl_status' => $d->ssl_status,
            ]);

        return Inertia::render('CustomDomains/Index', [
            'domains' => $domains,
            'cnameTarget' => $service->cnameTarget(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'domain' => [
                'required',
                'string',
                'max:253',
                'regex:/^([a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i',
                Rule::unique('custom_domains', 'domain'),
            ],
        ]);

        CustomDomain::create([
            'user_id' => $user->id,
            'domain' => mb_strtolower($validated['domain']),
            'status' => 'pending',
            'verification_token' => Str::random(32),
        ]);

        return back()->with('success', 'Domain added. Follow the DNS instructions to verify.');
    }

    public function verify(Request $request, CustomDomain $customDomain, CustomDomainService $service): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($customDomain->user_id !== $user->id) {
            abort(403);
        }

        $verified = $service->verify($customDomain);

        return back()->with(
            $verified ? 'success' : 'error',
            $verified ? 'Domain verified successfully!' : 'DNS verification failed. Check your DNS records and try again.'
        );
    }

    public function destroy(Request $request, CustomDomain $customDomain): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($customDomain->user_id !== $user->id) {
            abort(403);
        }

        $customDomain->delete();

        return back()->with('success', 'Domain removed.');
    }
}
