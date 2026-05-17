<?php

declare(strict_types=1);

namespace App\Http\Controllers\Status;

use App\Http\Controllers\Controller;
use App\Models\StatusCheck;
use App\Models\StatusIncident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class StatusPageController extends Controller
{
    private const SERVICES = [
        'web' => 'Web Application',
        'database' => 'Database',
        'cache' => 'Cache / Redis',
        'queue' => 'Job Queue',
        'qr_redirects' => 'QR Code Redirects',
    ];

    public function __invoke(Request $request): View|JsonResponse
    {
        $data = cache()->remember('status:page:data', 60, fn () => $this->buildStatusData());

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return view('status.index', $data);
    }

    /** @return array<string, mixed> */
    private function buildStatusData(): array
    {
        $serviceKeys = array_keys(self::SERVICES);
        $since90 = now()->subDays(90);
        $since30 = now()->subDays(30);

        // Query 1: latest check per service (subquery on MAX(id))
        /** @var Collection<string, StatusCheck> $latestChecks */
        $latestChecks = StatusCheck::whereIn('service', $serviceKeys)
            ->whereIn('id', function ($q) use ($serviceKeys): void {
                $q->selectRaw('MAX(id)')
                    ->from('status_checks')
                    ->whereIn('service', $serviceKeys)
                    ->groupBy('service');
            })
            ->get()
            ->keyBy('service');

        // Query 2: uptime stats for 90d + 30d in one conditional aggregate
        /** @var Collection<string, StatusCheck> $stats */
        $stats = StatusCheck::whereIn('service', $serviceKeys)
            ->where('checked_at', '>=', $since90)
            ->selectRaw(
                "service,
                COUNT(*) as total_90,
                SUM(CASE WHEN status = 'operational' THEN 1 ELSE 0 END) as op_90,
                SUM(CASE WHEN checked_at >= ? THEN 1 ELSE 0 END) as total_30,
                SUM(CASE WHEN status = 'operational' AND checked_at >= ? THEN 1 ELSE 0 END) as op_30",
                [$since30, $since30]
            )
            ->groupBy('service')
            ->get()
            ->keyBy('service');

        // Query 3: 90-day daily history for all services at once
        $historyRows = StatusCheck::whereIn('service', $serviceKeys)
            ->where('checked_at', '>=', $since90->copy()->startOfDay())
            ->selectRaw('service, DATE(checked_at) as day, MIN(status) as worst')
            ->groupByRaw('service, DATE(checked_at)')
            ->get();

        /** @var array<string, array<string, string>> $historyByService */
        $historyByService = [];
        foreach ($historyRows as $row) {
            /** @var array{service: string, day: string, worst: string} $attrs */
            $attrs = $row->getAttributes();
            $historyByService[$attrs['service']][$attrs['day']] = $attrs['worst'];
        }

        $services = [];
        foreach (self::SERVICES as $key => $label) {
            /** @var StatusCheck|null $latest */
            $latest = $latestChecks->get($key);

            /** @var StatusCheck|null $stat */
            $stat = $stats->get($key);

            /** @var array<string, mixed>|null $statAttrs */
            $statAttrs = $stat !== null ? $stat->getAttributes() : null;

            $uptime90 = $this->calcUptime($statAttrs, 'total_90', 'op_90');
            $uptime30 = $this->calcUptime($statAttrs, 'total_30', 'op_30');

            $indexed = $historyByService[$key] ?? [];
            $history = [];
            for ($i = 89; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $history[] = ['date' => $date, 'status' => $indexed[$date] ?? 'no-data'];
            }

            $services[$key] = [
                'label' => $label,
                'status' => $latest !== null ? $latest->status : 'no-data',
                'response_time_ms' => $latest?->response_time_ms,
                'last_checked' => $latest?->checked_at?->toIso8601String(),
                'uptime_90d' => $uptime90,
                'uptime_30d' => $uptime30,
                'history' => $history,
            ];
        }

        $overallStatus = $this->overallStatus($services);

        // Query 4: recent incidents
        $incidents = StatusIncident::where('starts_at', '>=', now()->subDays(30))
            ->orderByDesc('starts_at')
            ->limit(10)
            ->get()
            ->map(fn (StatusIncident $i) => [
                'id' => $i->id,
                'title' => $i->title,
                'description' => $i->description,
                'severity' => $i->severity,
                'status' => $i->status,
                'is_maintenance' => $i->is_maintenance,
                'starts_at' => $i->starts_at->toIso8601String(),
                'ends_at' => $i->ends_at?->toIso8601String(),
                'active' => $i->isActive(),
            ]);

        return [
            'overall_status' => $overallStatus,
            'services' => $services,
            'incidents' => $incidents,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $attrs
     */
    private function calcUptime(?array $attrs, string $totalKey, string $opKey): float
    {
        if ($attrs === null) {
            return 100.0;
        }
        $total = (int) ($attrs[$totalKey] ?? 0);
        if ($total === 0) {
            return 100.0;
        }

        return round((int) ($attrs[$opKey] ?? 0) / $total * 100, 2);
    }

    /** @param array<string, array<string, mixed>> $services */
    private function overallStatus(array $services): string
    {
        $statuses = array_column($services, 'status');

        if (in_array('outage', $statuses, true)) {
            return 'outage';
        }

        if (in_array('degraded', $statuses, true)) {
            return 'degraded';
        }

        return 'operational';
    }
}
