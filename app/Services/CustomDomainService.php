<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CustomDomain;

final class CustomDomainService
{
    public function cnameTarget(): string
    {
        return (string) config('app.custom_domain_cname_target', parse_url((string) config('app.url'), PHP_URL_HOST) ?? 'qr-master.app');
    }

    /**
     * Verify that the domain has a CNAME record pointing to our server
     * AND a TXT record with the verification token.
     */
    public function verify(CustomDomain $domain): bool
    {
        $cname = $this->checkCname($domain->domain);
        $txt = $this->checkTxtToken($domain->domain, $domain->verification_token);

        if ($cname && $txt) {
            $domain->update([
                'status' => 'verified',
                'verified_at' => now(),
            ]);

            return true;
        }

        $domain->update(['status' => 'failed']);

        return false;
    }

    private function checkCname(string $domain): bool
    {
        $target = $this->cnameTarget();
        $records = @dns_get_record($domain, DNS_CNAME);

        if (! is_array($records)) {
            return false;
        }

        foreach ($records as $record) {
            if (isset($record['target']) && rtrim((string) $record['target'], '.') === rtrim($target, '.')) {
                return true;
            }
        }

        return false;
    }

    private function checkTxtToken(string $domain, string $token): bool
    {
        $records = @dns_get_record("_qrmaster-verify.{$domain}", DNS_TXT);

        if (! is_array($records)) {
            return false;
        }

        foreach ($records as $record) {
            $txt = $record['txt'] ?? ($record['entries'][0] ?? '');
            if ($txt === "qrmaster-verification={$token}") {
                return true;
            }
        }

        return false;
    }
}
