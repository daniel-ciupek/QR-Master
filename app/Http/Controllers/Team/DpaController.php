<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Actions\Team\GenerateDpaAction;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class DpaController extends Controller
{
    public function show(Team $team): InertiaResponse
    {
        Gate::authorize('update', $team);

        $saved = (array) ($team->settings['dpa'] ?? []);

        return Inertia::render('Workspace/Dpa', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'prefill' => [
                'company_name' => $saved['company_name'] ?? '',
                'company_address' => $saved['company_address'] ?? '',
                'company_city' => $saved['company_city'] ?? '',
                'company_postal' => $saved['company_postal'] ?? '',
                'company_country' => $saved['company_country'] ?? '',
                'company_vat' => $saved['company_vat'] ?? '',
                'representative_name' => $saved['representative_name'] ?? '',
                'representative_email' => $saved['representative_email'] ?? '',
                'agreement_date' => $saved['agreement_date'] ?? now()->toDateString(),
            ],
        ]);
    }

    public function generate(Request $request, Team $team, GenerateDpaAction $action): Response
    {
        Gate::authorize('update', $team);

        /** @var array{
         *   company_name: string,
         *   company_address: string,
         *   company_city: string,
         *   company_postal: string,
         *   company_country: string,
         *   company_vat: string,
         *   representative_name: string,
         *   representative_email: string,
         *   agreement_date: string,
         * } $validated
         */
        $validated = Validator::make($request->all(), [
            'company_name' => ['required', 'string', 'max:200'],
            'company_address' => ['required', 'string', 'max:255'],
            'company_city' => ['required', 'string', 'max:100'],
            'company_postal' => ['required', 'string', 'max:20'],
            'company_country' => ['required', 'string', 'max:100'],
            'company_vat' => ['nullable', 'string', 'max:50'],
            'representative_name' => ['required', 'string', 'max:150'],
            'representative_email' => ['required', 'email', 'max:200'],
            'agreement_date' => ['required', 'date'],
        ])->validate();

        $pdf = $action->handle($team, $validated);

        $filename = 'DPA-'.str_replace(' ', '-', $team->name).'-'.$validated['agreement_date'].'.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
