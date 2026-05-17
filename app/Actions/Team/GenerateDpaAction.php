<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Models\Team;
use App\Models\User;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;

final class GenerateDpaAction
{
    /**
     * @param array{
     *   company_name: string,
     *   company_address: string,
     *   company_city: string,
     *   company_postal: string,
     *   company_country: string,
     *   company_vat: string,
     *   representative_name: string,
     *   representative_email: string,
     *   agreement_date: string,
     * } $data
     */
    public function handle(Team $team, array $data, ?User $actor = null): DomPDF
    {
        $settings = $team->settings ?? [];
        $settings['dpa'] = $data;
        $team->update(['settings' => $settings]);

        AuditLogger::log($team, 'dpa.generated', "DPA generated for {$data['company_name']}.", $actor, null, ['company' => $data['company_name'], 'date' => $data['agreement_date']]);

        return Pdf::loadView('pdf.dpa', [
            'team' => $team,
            'controller' => $data,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');
    }
}
