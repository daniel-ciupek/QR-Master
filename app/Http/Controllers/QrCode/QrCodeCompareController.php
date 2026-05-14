<?php

declare(strict_types=1);

namespace App\Http\Controllers\QrCode;

use App\Http\Controllers\Controller;
use App\Http\Requests\QrCode\CompareAnalyticsRequest;
use App\Models\QrCode;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class QrCodeCompareController extends Controller
{
    public function __invoke(CompareAnalyticsRequest $request): Response
    {
        $ids = $request->validated()['ids'];
        $userId = $request->user()?->id;

        abort_if($userId === null, 403);

        $qrCodes = QrCode::whereIn('id', $ids)
            ->where('user_id', $userId)
            ->get();

        abort_if($qrCodes->count() < 2, 403);

        $items = $qrCodes->map(function (QrCode $qrCode): array {
            $stats = [
                'total' => DB::table('scan_logs')->where('qr_code_id', $qrCode->id)->count(),
                'unique' => DB::table('scan_logs')->where('qr_code_id', $qrCode->id)->distinct('ip_hash')->count('ip_hash'),
                'today' => DB::table('scan_logs')->where('qr_code_id', $qrCode->id)->whereDate('scanned_at', today())->count(),
                'this_week' => DB::table('scan_logs')->where('qr_code_id', $qrCode->id)->where('scanned_at', '>=', now()->startOfWeek())->count(),
                'this_month' => DB::table('scan_logs')->where('qr_code_id', $qrCode->id)->where('scanned_at', '>=', now()->startOfMonth())->count(),
            ];

            /** @var list<array{country: string, count: int}> $topCountries */
            $topCountries = DB::table('scan_logs')
                ->where('qr_code_id', $qrCode->id)
                ->whereNotNull('country')
                ->groupBy('country')
                ->select('country', DB::raw('count(*) as count'))
                ->orderByDesc('count')
                ->limit(3)
                ->get()
                ->map(fn (object $r): array => ['country' => (string) $r->country, 'count' => (int) $r->count])
                ->all();

            /** @var list<array{type: string, count: int}> $deviceBreakdown */
            $deviceBreakdown = DB::table('scan_logs')
                ->where('qr_code_id', $qrCode->id)
                ->groupBy('device_type')
                ->select(DB::raw("coalesce(device_type, 'unknown') as type"), DB::raw('count(*) as count'))
                ->orderByDesc('count')
                ->get()
                ->map(fn (object $r): array => ['type' => (string) $r->type, 'count' => (int) $r->count])
                ->all();

            /** @var list<array{date: string, count: int}> $dailyScans */
            $dailyScans = DB::table('scan_logs')
                ->where('qr_code_id', $qrCode->id)
                ->where('scanned_at', '>=', now()->subDays(29)->startOfDay())
                ->groupBy('date')
                ->select(DB::raw('DATE(scanned_at) as date'), DB::raw('count(*) as count'))
                ->orderBy('date')
                ->get()
                ->map(fn (object $r): array => ['date' => (string) $r->date, 'count' => (int) $r->count])
                ->all();

            return [
                'id' => $qrCode->id,
                'title' => $qrCode->title,
                'short_hash' => $qrCode->short_hash,
                'type' => $qrCode->type->value,
                'destination_url' => $qrCode->destination_url,
                'is_active' => $qrCode->is_active,
                'created_at' => $qrCode->created_at->toDateString(),
                'stats' => $stats,
                'topCountries' => $topCountries,
                'deviceBreakdown' => $deviceBreakdown,
                'dailyScans' => $dailyScans,
            ];
        })->values()->all();

        return Inertia::render('QrCode/Compare', [
            'items' => $items,
        ]);
    }
}
