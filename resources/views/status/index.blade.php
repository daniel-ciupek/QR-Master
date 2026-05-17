<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR-Master System Status</title>
    <meta http-equiv="refresh" content="60">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif; background: #f8fafc; color: #0f172a; line-height: 1.5; }
        .container { max-width: 860px; margin: 0 auto; padding: 2rem 1.5rem; }

        /* Header */
        .header { text-align: center; margin-bottom: 2.5rem; }
        .logo { font-size: 1.5rem; font-weight: 800; letter-spacing: -0.5px; color: #6366f1; margin-bottom: 0.5rem; }
        .logo span { color: #0f172a; }
        .header p { font-size: 0.9rem; color: #64748b; }

        /* Overall status banner */
        .status-banner { display: flex; align-items: center; justify-content: space-between; border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 2rem; }
        .status-banner.operational { background: #f0fdf4; border: 1px solid #bbf7d0; }
        .status-banner.degraded { background: #fffbeb; border: 1px solid #fde68a; }
        .status-banner.outage { background: #fef2f2; border: 1px solid #fecaca; }
        .status-banner .label { font-size: 1.1rem; font-weight: 700; }
        .status-banner.operational .label { color: #15803d; }
        .status-banner.degraded .label { color: #92400e; }
        .status-banner.outage .label { color: #b91c1c; }
        .status-banner .dot { width: 12px; height: 12px; border-radius: 50%; margin-right: 0.75rem; flex-shrink: 0; }
        .operational .dot { background: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,0.25); }
        .degraded .dot { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.25); }
        .outage .dot { background: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.25); }
        .generated { font-size: 0.75rem; color: #94a3b8; }

        /* Section headers */
        h2 { font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; }

        /* Service rows */
        .services { margin-bottom: 2rem; }
        .service-row { display: flex; align-items: flex-start; justify-content: space-between; padding: 1rem 0; border-bottom: 1px solid #f1f5f9; gap: 1rem; }
        .service-row:last-child { border-bottom: none; }
        .service-label { font-weight: 600; font-size: 0.9rem; min-width: 160px; }
        .service-meta { font-size: 0.75rem; color: #64748b; margin-top: 2px; }
        .service-uptime { font-size: 0.75rem; color: #64748b; margin-top: 2px; }
        .service-history { display: flex; gap: 2px; align-items: center; flex-wrap: nowrap; overflow: hidden; }
        .day-dot { width: 8px; height: 28px; border-radius: 2px; flex-shrink: 0; }
        .day-dot.operational { background: #22c55e; }
        .day-dot.degraded { background: #f59e0b; }
        .day-dot.outage { background: #ef4444; }
        .day-dot.no-data { background: #e2e8f0; }
        .service-status-badge { font-size: 0.75rem; font-weight: 600; border-radius: 999px; padding: 0.25rem 0.75rem; min-width: 90px; text-align: center; flex-shrink: 0; }
        .badge-operational { background: #dcfce7; color: #15803d; }
        .badge-degraded { background: #fef3c7; color: #92400e; }
        .badge-outage { background: #fee2e2; color: #b91c1c; }
        .badge-no-data { background: #f1f5f9; color: #64748b; }

        /* Incidents */
        .incidents { margin-bottom: 2rem; }
        .incident-card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 0.75rem; }
        .incident-card.active { border-color: #fde68a; background: #fffbeb; }
        .incident-card.maintenance { border-color: #bfdbfe; background: #eff6ff; }
        .incident-title { font-weight: 700; font-size: 0.9rem; margin-bottom: 0.25rem; }
        .incident-meta { font-size: 0.75rem; color: #64748b; display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.25rem; }
        .incident-desc { font-size: 0.85rem; color: #374151; margin-top: 0.5rem; }
        .incident-badge { font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.6rem; border-radius: 999px; text-transform: uppercase; letter-spacing: 0.5px; }
        .severity-minor { background: #f1f5f9; color: #475569; }
        .severity-major { background: #fef3c7; color: #92400e; }
        .severity-critical { background: #fee2e2; color: #b91c1c; }
        .status-resolved { background: #dcfce7; color: #15803d; }
        .status-monitoring { background: #dbeafe; color: #1d4ed8; }
        .status-investigating { background: #fef3c7; color: #92400e; }

        .no-incidents { text-align: center; color: #94a3b8; font-size: 0.9rem; padding: 2rem; }
        .footer { text-align: center; font-size: 0.75rem; color: #94a3b8; margin-top: 2rem; }
        .history-label { font-size: 0.7rem; color: #94a3b8; margin-top: 0.25rem; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">QR<span>-Master</span></div>
        <p>System Status Dashboard</p>
    </div>

    <div class="status-banner {{ $overall_status }}">
        <div style="display:flex;align-items:center">
            <div class="dot"></div>
            <span class="label">
                @if($overall_status === 'operational') All Systems Operational
                @elseif($overall_status === 'degraded') Some Systems Degraded
                @else Service Disruption Detected
                @endif
            </span>
        </div>
        <span class="generated">Updated {{ \Carbon\Carbon::parse($generated_at)->format('d M Y H:i') }} UTC</span>
    </div>

    <div class="services">
        <h2>Services</h2>
        @foreach($services as $key => $service)
        <div class="service-row">
            <div>
                <div class="service-label">{{ $service['label'] }}</div>
                @if($service['response_time_ms'])
                <div class="service-meta">{{ $service['response_time_ms'] }}ms response</div>
                @endif
                <div class="service-uptime">
                    {{ $service['uptime_90d'] }}% uptime (90d) &nbsp;·&nbsp; {{ $service['uptime_30d'] }}% (30d)
                </div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px">
                <span class="service-status-badge badge-{{ $service['status'] }}">
                    @if($service['status'] === 'operational') ✓ Operational
                    @elseif($service['status'] === 'degraded') ⚠ Degraded
                    @elseif($service['status'] === 'outage') ✕ Outage
                    @else – No data
                    @endif
                </span>
                <div class="service-history" title="90-day history">
                    @foreach($service['history'] as $day)
                    <div class="day-dot {{ $day['status'] }}" title="{{ $day['date'] }}: {{ $day['status'] }}"></div>
                    @endforeach
                </div>
                <div class="history-label" style="width:100%">
                    <span>90 days ago</span>
                    <span>Today</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="incidents">
        <h2>Recent Incidents (30 days)</h2>
        @forelse($incidents as $incident)
        <div class="incident-card {{ $incident['active'] ? ($incident['is_maintenance'] ? 'maintenance' : 'active') : '' }}">
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap">
                <span class="incident-title">{{ $incident['title'] }}</span>
                <span class="incident-badge severity-{{ $incident['severity'] }}">{{ $incident['severity'] }}</span>
                <span class="incident-badge status-{{ $incident['status'] }}">{{ str_replace('_', ' ', $incident['status']) }}</span>
                @if($incident['is_maintenance'])<span class="incident-badge" style="background:#dbeafe;color:#1d4ed8">Maintenance</span>@endif
            </div>
            <div class="incident-meta">
                <span>Started: {{ \Carbon\Carbon::parse($incident['starts_at'])->format('d M Y H:i') }} UTC</span>
                @if($incident['ends_at'])<span>Resolved: {{ \Carbon\Carbon::parse($incident['ends_at'])->format('d M Y H:i') }} UTC</span>@endif
            </div>
            @if($incident['description'])
            <div class="incident-desc">{{ $incident['description'] }}</div>
            @endif
        </div>
        @empty
        <div class="no-incidents">✓ No incidents in the past 30 days</div>
        @endforelse
    </div>

    <div class="footer">
        <p>QR-Master Status &nbsp;·&nbsp; Checks run every 5 minutes &nbsp;·&nbsp; Page auto-refreshes every 60 seconds</p>
    </div>
</div>
</body>
</html>
