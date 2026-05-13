<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ScanLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('privacy:purge-old-scans {--days=90 : Retention period in days}')]
#[Description('Anonymize geo data in scan_logs older than the retention period (RODO/GDPR)')]
final class PurgeOldScans extends Command
{
    public function handle(): int
    {
        $days = (int) $this->option('days');

        $affected = ScanLog::query()
            ->where('scanned_at', '<', now()->subDays($days))
            ->whereNotNull('country')
            ->update([
                'country' => null,
                'region' => null,
                'city' => null,
                'lat' => null,
                'lng' => null,
            ]);

        $this->info("Purged geo data from {$affected} scan_logs older than {$days} days.");

        return self::SUCCESS;
    }
}
