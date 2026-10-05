<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouterBandwidthLog extends Model
{
    protected $fillable = [
        'router_id',
        'interface_name',
        'rx_bytes',
        'tx_bytes',
        'rx_delta_bytes',
        'tx_delta_bytes',
        'rx_speed_bps',
        'tx_speed_bps',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at'    => 'datetime',
            'rx_bytes'       => 'integer',
            'tx_bytes'       => 'integer',
            'rx_delta_bytes' => 'integer',
            'tx_delta_bytes' => 'integer',
            'rx_speed_bps'   => 'integer',
            'tx_speed_bps'   => 'integer',
        ];
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class, 'router_id', 'id');
    }

    /**
     * Get aggregate bandwidth consumption (Today, This Week, This Month).
     */
    public static function getConsumptionStats(int $routerId, ?string $interfaceName = null): array
    {
        $now = Carbon::now();
        $startOfDay = $now->copy()->startOfDay();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfMonth = $now->copy()->startOfMonth();

        $query = self::where('router_id', $routerId);
        if ($interfaceName) {
            $query->where('interface_name', $interfaceName);
        }

        // Today
        $todayQuery = (clone $query)->where('recorded_at', '>=', $startOfDay);
        $todayRx = (float) $todayQuery->sum('rx_delta_bytes');
        $todayTx = (float) $todayQuery->sum('tx_delta_bytes');

        // This Week (last 7 days / from Monday)
        $weekQuery = (clone $query)->where('recorded_at', '>=', $startOfWeek);
        $weekRx = (float) $weekQuery->sum('rx_delta_bytes');
        $weekTx = (float) $weekQuery->sum('tx_delta_bytes');

        // This Month
        $monthQuery = (clone $query)->where('recorded_at', '>=', $startOfMonth);
        $monthRx = (float) $monthQuery->sum('rx_delta_bytes');
        $monthTx = (float) $monthQuery->sum('tx_delta_bytes');

        return [
            'today' => [
                'rx'        => $todayRx,
                'tx'        => $todayTx,
                'total'     => $todayRx + $todayTx,
                'formatted' => self::formatBytes($todayRx + $todayTx),
                'rx_fmt'    => self::formatBytes($todayRx),
                'tx_fmt'    => self::formatBytes($todayTx),
            ],
            'weekly' => [
                'rx'        => $weekRx,
                'tx'        => $weekTx,
                'total'     => $weekRx + $weekTx,
                'formatted' => self::formatBytes($weekRx + $weekTx),
                'rx_fmt'    => self::formatBytes($weekRx),
                'tx_fmt'    => self::formatBytes($weekTx),
            ],
            'monthly' => [
                'rx'        => $monthRx,
                'tx'        => $monthTx,
                'total'     => $monthRx + $monthTx,
                'formatted' => self::formatBytes($monthRx + $monthTx),
                'rx_fmt'    => self::formatBytes($monthRx),
                'tx_fmt'    => self::formatBytes($monthTx),
            ],
        ];
    }

    public static function formatBytes(float $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        $value = $bytes / pow(1024, $power);
        return round($value, $precision) . ' ' . $units[$power];
    }
}
