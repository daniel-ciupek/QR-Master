<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class AuditReportController extends Controller
{
    private const PER_PAGE = 50;

    public function show(Request $request, Team $team): InertiaResponse
    {
        Gate::authorize('update', $team);

        $query = AuditLog::where('team_id', $team->id)
            ->with('user:id,name,email')
            ->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->string('from')->toString());
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->string('to')->toString());
        }

        $logs = $query->paginate(self::PER_PAGE)->withQueryString();

        $members = $team->members()
            ->select('users.id', 'users.name', 'users.email')
            ->get();

        $actions = AuditLog::where('team_id', $team->id)
            ->distinct('action')
            ->pluck('action')
            ->sort()
            ->values();

        return Inertia::render('Workspace/AuditReport', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'logs' => $logs,
            'members' => $members,
            'actions' => $actions,
            'filters' => $request->only(['action', 'user_id', 'from', 'to']),
        ]);
    }

    public function export(Request $request, Team $team): Response
    {
        Gate::authorize('update', $team);

        $query = AuditLog::where('team_id', $team->id)
            ->with('user:id,name,email')
            ->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->string('from')->toString());
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->string('to')->toString());
        }

        $logs = $query->limit(10_000)->get();

        $csv = "Date,User,Email,Action,Description,Subject\n";

        foreach ($logs as $log) {
            $csv .= implode(',', [
                '"'.$log->created_at->toIso8601String().'"',
                '"'.($log->user !== null ? $log->user->name : 'System').'"',
                '"'.($log->user !== null ? $log->user->email : '').'"',
                '"'.$log->action.'"',
                '"'.str_replace('"', '""', $log->description).'"',
                '"'.($log->subject_type ? $log->subject_type.'#'.$log->subject_id : '').'"',
            ])."\n";
        }

        $filename = 'audit-log-'.$team->slug.'-'.now()->format('Y-m-d').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
