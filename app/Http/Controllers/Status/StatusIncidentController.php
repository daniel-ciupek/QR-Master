<?php

declare(strict_types=1);

namespace App\Http\Controllers\Status;

use App\Http\Controllers\Controller;
use App\Models\StatusIncident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class StatusIncidentController extends Controller
{
    public function index(): Response
    {
        $incidents = StatusIncident::orderByDesc('starts_at')->paginate(20);

        return Inertia::render('Admin/StatusIncidents', [
            'incidents' => $incidents,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'severity' => ['required', 'in:minor,major,critical'],
            'status' => ['required', 'in:investigating,identified,monitoring,resolved'],
            'is_maintenance' => ['boolean'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        StatusIncident::create($validated);

        return redirect()->route('admin.status.incidents.index')
            ->with('success', 'Incident created.');
    }

    public function update(Request $request, StatusIncident $incident): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'severity' => ['required', 'in:minor,major,critical'],
            'status' => ['required', 'in:investigating,identified,monitoring,resolved'],
            'is_maintenance' => ['boolean'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date'],
        ]);

        $incident->update($validated);

        return redirect()->route('admin.status.incidents.index')
            ->with('success', 'Incident updated.');
    }

    public function destroy(StatusIncident $incident): RedirectResponse
    {
        $incident->delete();

        return redirect()->route('admin.status.incidents.index')
            ->with('success', 'Incident deleted.');
    }
}
