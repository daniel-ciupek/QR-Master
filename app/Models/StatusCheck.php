<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $service
 * @property string $status
 * @property int|null $response_time_ms
 * @property string|null $message
 * @property Carbon $checked_at
 */
class StatusCheck extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return ['checked_at' => 'datetime'];
    }

    public static function uptimePercent(string $service, int $days = 90): float
    {
        $total = self::where('service', $service)
            ->where('checked_at', '>=', now()->subDays($days))
            ->count();

        if ($total === 0) {
            return 100.0;
        }

        $operational = self::where('service', $service)
            ->where('checked_at', '>=', now()->subDays($days))
            ->where('status', 'operational')
            ->count();

        return round(($operational / $total) * 100, 2);
    }

    /** @return list<array{date: string, status: string}> */
    public static function dailyHistory(string $service, int $days = 90): array
    {
        /** @var array<string, string> $indexed */
        $indexed = [];

        $rows = self::where('service', $service)
            ->where('checked_at', '>=', now()->subDays($days)->startOfDay())
            ->selectRaw('DATE(checked_at) as day, MIN(status) as worst')
            ->groupByRaw('DATE(checked_at)')
            ->orderByRaw('DATE(checked_at)')
            ->get(['checked_at', 'status']);

        foreach ($rows as $row) {
            /** @var array{day: string, worst: string} $attrs */
            $attrs = $row->getAttributes();
            $indexed[$attrs['day']] = $attrs['worst'];
        }

        $history = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $history[] = [
                'date' => $date,
                'status' => $indexed[$date] ?? 'no-data',
            ];
        }

        return $history;
    }
}
