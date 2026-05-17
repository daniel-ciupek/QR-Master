<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\StatusCheck;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

#[Signature('status:check-services')]
#[Description('Run health checks for all services and record results')]
final class CheckServices extends Command
{
    /** @var list<array{name: string, method: string}> */
    private const SERVICES = [
        ['name' => 'web', 'method' => 'checkWeb'],
        ['name' => 'database', 'method' => 'checkDatabase'],
        ['name' => 'cache', 'method' => 'checkCache'],
        ['name' => 'queue', 'method' => 'checkQueue'],
        ['name' => 'qr_redirects', 'method' => 'checkQrRedirects'],
    ];

    public function handle(): int
    {
        foreach (self::SERVICES as $service) {
            [$status, $ms, $message] = $this->{$service['method']}();

            StatusCheck::create([
                'service' => $service['name'],
                'status' => $status,
                'response_time_ms' => $ms,
                'message' => $message,
            ]);
        }

        // Prune checks older than 90 days to keep table lean
        StatusCheck::where('checked_at', '<', now()->subDays(91))->delete();

        $this->info('Health checks recorded at '.now()->toIso8601String());

        return self::SUCCESS;
    }

    /** @return array{0: string, 1: int|null, 2: string|null} */
    private function checkWeb(): array
    {
        try {
            $start = microtime(true);
            $response = Http::timeout(5)->get(config('app.url').'/up');
            $ms = (int) ((microtime(true) - $start) * 1000);

            if ($response->successful()) {
                return ['operational', $ms, null];
            }

            return ['degraded', $ms, 'HTTP '.$response->status()];
        } catch (\Throwable $e) {
            return ['outage', null, 'Connection failed: '.substr($e->getMessage(), 0, 100)];
        }
    }

    /** @return array{0: string, 1: int|null, 2: string|null} */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::selectOne('SELECT 1');
            $ms = (int) ((microtime(true) - $start) * 1000);

            return ['operational', $ms, null];
        } catch (\Throwable $e) {
            return ['outage', null, 'DB error: '.substr($e->getMessage(), 0, 100)];
        }
    }

    /** @return array{0: string, 1: int|null, 2: string|null} */
    private function checkCache(): array
    {
        try {
            $start = microtime(true);
            $key = 'status:ping:'.time();
            Redis::setex($key, 10, '1');
            $val = Redis::get($key);
            Redis::del($key);
            $ms = (int) ((microtime(true) - $start) * 1000);

            return $val === '1' ? ['operational', $ms, null] : ['degraded', $ms, 'Cache read mismatch'];
        } catch (\Throwable $e) {
            return ['outage', null, 'Redis error: '.substr($e->getMessage(), 0, 100)];
        }
    }

    /** @return array{0: string, 1: int|null, 2: string|null} */
    private function checkQueue(): array
    {
        try {
            $size = (int) Redis::llen('queues:default');
            // > 1000 pending jobs = degraded
            $status = $size > 1000 ? 'degraded' : 'operational';

            return [$status, null, $size > 1000 ? "Queue backlog: {$size} jobs" : null];
        } catch (\Throwable $e) {
            return ['degraded', null, 'Cannot check queue: '.substr($e->getMessage(), 0, 100)];
        }
    }

    /** @return array{0: string, 1: int|null, 2: string|null} */
    private function checkQrRedirects(): array
    {
        try {
            $start = microtime(true);
            // Check a non-existent hash — should return 404 (not 5xx)
            $response = Http::timeout(5)->get(config('app.url').'/q/healthcheck-probe-000');
            $ms = (int) ((microtime(true) - $start) * 1000);

            $ok = in_array($response->status(), [200, 302, 404], true);

            return [$ok ? 'operational' : 'degraded', $ms, $ok ? null : 'Unexpected status '.$response->status()];
        } catch (\Throwable $e) {
            return ['outage', null, 'Request failed: '.substr($e->getMessage(), 0, 100)];
        }
    }
}
