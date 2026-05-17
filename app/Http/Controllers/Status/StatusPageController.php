<?php

declare(strict_types=1);

namespace App\Http\Controllers\Status;

use App\Http\Controllers\Controller;
use App\Models\StatusCheck;
use App\Models\StatusIncident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $data = $this->buildStatusData();

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return view('status.index', $data);
    }

    /** @return array<string, mixed> */
    private function buildStatusData(): array
    {
        $services = [];

        foreach (self::SERVICES as $key => $label) {
            $latest = StatusCheck::where('service', $key)
                ->orderByDesc('checked_at')
                ->first();

            $uptime90 = StatusCheck::uptimePercent($key, 90);
            $uptime30 = StatusCheck::uptimePercent($key, 30);
            $history = StatusCheck::dailyHistory($key, 90);

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
