<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Team;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $team = null;

        if ($user->current_team_id !== null) {
            $team = Team::find($user->current_team_id);

            // Gracefully reset if user no longer belongs to the team
            if ($team !== null && ! $user->belongsToTeam($team)) {
                $user->update(['current_team_id' => null]);
                $team = null;
            }
        }

        app()->instance('current.team', $team);
        $request->attributes->set('current_team', $team);

        return $next($request);
    }
}
