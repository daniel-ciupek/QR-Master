<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Actions\Team\UpdateTeamBrandingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\UpdateTeamBrandingRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
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

    public function update(UpdateTeamBrandingRequest $request, Team $team, UpdateTeamBrandingAction $action): RedirectResponse
    {
        Gate::authorize('update', $team);

        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validated();

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
            $actor,
        );

        return back()->with('success', __('workspace.branding_updated'));
    }
}
