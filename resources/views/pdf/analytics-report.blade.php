<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Analytics Report — {{ $qrCode->title }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1a1a2e;
            background: #ffffff;
            line-height: 1.5;
        }

        /* ── Header ── */
        .header {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
            color: #ffffff;
            padding: 28px 32px 22px;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 18px;
        }
        .brand {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #60a5fa;
        }
        .brand span { color: #ffffff; }
        .report-badge {
            background: rgba(96,165,250,0.18);
            border: 1px solid rgba(96,165,250,0.4);
            color: #93c5fd;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 4px;
        }
        .qr-title {
            font-size: 22px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 6px;
        }
        .qr-meta {
            font-size: 10px;
            color: #94a3b8;
            display: flex;
            gap: 20px;
        }
        .qr-meta span { display: inline-block; }
        .status-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .status-active   { background: #166534; color: #86efac; }
        .status-inactive { background: #7f1d1d; color: #fca5a5; }

        /* ── Body ── */
        .body { padding: 24px 32px; }

        /* ── Section title ── */
        .section-title {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e2e8f0;
        }

        /* ── Stat cards ── */
        .stat-grid {
            display: flex;
            gap: 10px;
            margin-bottom: 22px;
        }
        .stat-card {
            flex: 1;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 14px;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.1;
        }
        .stat-label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: 4px;
        }
        .stat-card.highlight {
            background: #eff6ff;
            border-color: #bfdbfe;
        }
        .stat-card.highlight .stat-value { color: #1d4ed8; }

        /* ── Two-column layout ── */
        .two-col {
            display: flex;
            gap: 16px;
            margin-bottom: 22px;
        }
        .col { flex: 1; }

        /* ── Tables ── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        thead tr { background: #f1f5f9; }
        thead th {
            padding: 7px 10px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px solid #e2e8f0;
        }
        thead th.right { text-align: right; }
        tbody tr { border-bottom: 1px solid #f1f5f9; }
        tbody tr:last-child { border-bottom: none; }
        tbody td { padding: 7px 10px; color: #334155; }
        tbody td.right { text-align: right; font-weight: 600; color: #0f172a; }
        .table-wrap {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        /* ── Bar chart ── */
        .bar-chart { margin-bottom: 22px; }
        .bar-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }
        .bar-label {
            width: 60px;
            font-size: 9px;
            color: #64748b;
            text-align: right;
            flex-shrink: 0;
        }
        .bar-track {
            flex: 1;
            background: #f1f5f9;
            border-radius: 3px;
            height: 14px;
            overflow: hidden;
        }
        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%);
            border-radius: 3px;
            min-width: 2px;
        }
        .bar-count {
            width: 32px;
            font-size: 9px;
            font-weight: 600;
            color: #334155;
            text-align: right;
            flex-shrink: 0;
        }

        /* ── Pill / badge ── */
        .device-pill {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
            text-transform: capitalize;
        }

        /* ── Progress bar (device/browser pct) ── */
        .pct-bar {
            display: inline-block;
            background: #e0f2fe;
            border-radius: 3px;
            height: 8px;
            vertical-align: middle;
            margin-right: 4px;
        }
        .pct-fill {
            display: block;
            height: 100%;
            background: #0ea5e9;
            border-radius: 3px;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 24px;
            padding-top: 14px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: #94a3b8;
        }
        .footer strong { color: #64748b; }

        /* ── Page break ── */
        .page-break { page-break-before: always; }

        /* ── Empty state ── */
        .empty {
            text-align: center;
            padding: 20px;
            color: #94a3b8;
            font-size: 10px;
            font-style: italic;
        }
    </style>
</head>
<body>

{{-- ── HEADER ── --}}
<div class="header">
    <div class="header-top">
        <div class="brand">QR<span>Master</span></div>
        <div class="report-badge">Analytics Report</div>
    </div>
    <div class="qr-title">{{ $qrCode->title }}</div>
    <div class="qr-meta">
        <span>{{ $qrCode->short_hash }}</span>
        @if($qrCode->destination_url)
            <span>{{ Str::limit($qrCode->destination_url, 60) }}</span>
        @endif
        <span>Created {{ $qrCode->created_at->format('d M Y') }}</span>
        <span>
            <span class="status-pill {{ $qrCode->is_active ? 'status-active' : 'status-inactive' }}">
                {{ $qrCode->is_active ? 'Active' : 'Inactive' }}
            </span>
        </span>
    </div>
</div>

{{-- ── BODY ── --}}
<div class="body">

    {{-- ── Stat cards ── --}}
    <div class="section-title">Scan Summary</div>
    <div class="stat-grid">
        <div class="stat-card highlight">
            <div class="stat-value">{{ number_format($stats['total']) }}</div>
            <div class="stat-label">Total Scans</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['unique']) }}</div>
            <div class="stat-label">Unique Visitors</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['today']) }}</div>
            <div class="stat-label">Today</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['this_week']) }}</div>
            <div class="stat-label">This Week</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['this_month']) }}</div>
            <div class="stat-label">This Month</div>
        </div>
    </div>

    {{-- ── Daily bar chart (last 30 days) ── --}}
    <div class="section-title">Scans — Last 30 Days</div>
    <div class="bar-chart">
        @if($dailyScans->isEmpty())
            <div class="empty">No scan data for the last 30 days.</div>
        @else
            @foreach($dailyScans as $day)
                @php
                    $pct = $maxDailyCount > 0 ? round(($day->count / $maxDailyCount) * 100) : 0;
                    $label = \Carbon\Carbon::parse($day->date)->format('d M');
                @endphp
                <div class="bar-row">
                    <div class="bar-label">{{ $label }}</div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width: {{ $pct }}%;"></div>
                    </div>
                    <div class="bar-count">{{ $day->count }}</div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- ── Countries + Devices ── --}}
    <div class="two-col">
        {{-- Countries --}}
        <div class="col">
            <div class="section-title">Top Countries</div>
            @if($topCountries->isEmpty())
                <div class="empty">No country data yet.</div>
            @else
                @php $maxCountry = $topCountries->max('count') ?: 1; @endphp
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Country</th>
                                <th class="right">Scans</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topCountries as $i => $row)
                                <tr>
                                    <td style="color:#94a3b8;width:20px;">{{ $i + 1 }}</td>
                                    <td>{{ $row->country }}</td>
                                    <td class="right">{{ number_format($row->count) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Devices --}}
        <div class="col">
            <div class="section-title">Device Breakdown</div>
            @if($deviceBreakdown->isEmpty())
                <div class="empty">No device data yet.</div>
            @else
                @php
                    $totalDevices = $deviceBreakdown->sum('count') ?: 1;
                @endphp
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Device</th>
                                <th class="right">%</th>
                                <th class="right">Scans</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deviceBreakdown as $row)
                                @php $pct = round(($row->count / $totalDevices) * 100); @endphp
                                <tr>
                                    <td><span class="device-pill">{{ $row->type }}</span></td>
                                    <td class="right" style="color:#64748b;">{{ $pct }}%</td>
                                    <td class="right">{{ number_format($row->count) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Browsers ── --}}
    <div class="section-title">Top Browsers</div>
    @if($topBrowsers->isEmpty())
        <div class="empty">No browser data yet.</div>
    @else
        @php $maxBrowser = $topBrowsers->max('count') ?: 1; @endphp
        <div class="table-wrap" style="margin-bottom:22px;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Browser</th>
                        <th class="right">Scans</th>
                        <th class="right">Share</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topBrowsers as $i => $row)
                        @php
                            $total = $stats['total'] ?: 1;
                            $share = round(($row->count / $total) * 100, 1);
                        @endphp
                        <tr>
                            <td style="color:#94a3b8;width:20px;">{{ $i + 1 }}</td>
                            <td>{{ $row->browser }}</td>
                            <td class="right">{{ number_format($row->count) }}</td>
                            <td class="right" style="color:#64748b;">{{ $share }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- ── Recent Scans ── --}}
    <div class="section-title">Recent Scans (last 20)</div>
    @if($recentScans->isEmpty())
        <div class="empty">No scans recorded yet.</div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>Location</th>
                        <th>Device</th>
                        <th>OS</th>
                        <th>Browser</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentScans as $scan)
                        <tr>
                            <td style="white-space:nowrap;color:#64748b;">
                                {{ \Carbon\Carbon::parse($scan->scanned_at)->format('d M Y H:i') }}
                            </td>
                            <td>
                                @if($scan->city || $scan->country)
                                    {{ implode(', ', array_filter([$scan->city, $scan->country])) }}
                                @else
                                    <span style="color:#cbd5e1;">—</span>
                                @endif
                            </td>
                            <td>{{ $scan->device_type ?? '—' }}</td>
                            <td>{{ $scan->os ?? '—' }}</td>
                            <td>{{ $scan->browser ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- ── Footer ── --}}
    <div class="footer">
        <div>
            <strong>QRMaster</strong> &mdash; Analytics Report
        </div>
        <div>
            Generated {{ $generatedAt->format('d M Y, H:i') }} UTC
        </div>
    </div>

</div>
</body>
</html>
