<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Actions\Team\SwitchTeamAction;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;

class TeamSwitchController
{
    public function __invoke(SwitchTeamAction $action, ?Team $team = null): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        try {
            $action->handle($user, $team);
        } catch (AuthorizationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('dashboard')
            ->with('success', $team !== null
                ? __('workspace.switched', ['name' => $team->name])
                : __('workspace.switched_personal'));
    }
}
