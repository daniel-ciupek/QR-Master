<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Actions\Team\UpdateTeamBrandingAction;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

final class TeamBrandingController extends Controller
{
    public function show(Team $team): Response
    {
        Gate::authorize('update', $team);

        return Inertia::render('Workspace/Branding', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'branding' => $team->brandingSettings(),
        ]);
    }

    public function update(Request $request, Team $team, UpdateTeamBrandingAction $action): RedirectResponse
    {
        Gate::authorize('update', $team);

        $validated = Validator::make($request->all(), [
            'brand_name' => ['nullable', 'string', 'max:60'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'powered_by_hidden' => ['sometimes', 'boolean'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
        ])->validate();

        $logo = $request->hasFile('logo') ? $request->file('logo') : null;

        $action->handle(
            $team,
            [
                'brand_name' => $validated['brand_name'] ?? null,
                'primary_color' => $validated['primary_color'] ?? null,
                'powered_by_hidden' => (bool) ($validated['powered_by_hidden'] ?? false),
            ],
            is_array($logo) ? null : $logo,
            (bool) ($validated['remove_logo'] ?? false),
        );

        return back()->with('success', __('workspace.branding_updated'));
    }
}
