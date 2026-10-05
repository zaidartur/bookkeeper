<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NetworkMonitor extends Model
{
    protected $fillable = [
        'name',
        'ip_address',
        'port',
        'type',
        'status',
        'response_time_ms',
        'last_checked_at',
        'last_status_change_at',
        'fail_count',
        'uptime_percentage',
        'is_active',
        'notify_telegram',
        'keterangan',
    ];

    protected $casts = [
        'port' => 'integer',
        'response_time_ms' => 'float',
        'last_checked_at' => 'datetime',
        'last_status_change_at' => 'datetime',
        'fail_count' => 'integer',
        'uptime_percentage' => 'float',
        'is_active' => 'boolean',
        'notify_telegram' => 'boolean',
    ];

    public function uptimeLogs(): HasMany
    {
        return $this->hasMany(NetworkUptimeLog::class, 'network_monitor_id');
    }

    public function isUp(): bool
    {
        return $this->status === 'UP';
    }

    public function isDown(): bool
    {
        return $this->status === 'DOWN';
    }

    /**
     * Recalculates uptime percentage over the past X hours (default: 24h).
     */
    public function calculateUptimePercentage(int $hours = 24): float
    {
        $since = Carbon::now()->subHours($hours);
        $totalLogs = $this->uptimeLogs()->where('checked_at', '>=', $since)->count();

        if ($totalLogs === 0) {
            return $this->uptime_percentage ?? 100.0;
        }

        $upLogs = $this->uptimeLogs()
            ->where('checked_at', '>=', $since)
            ->where('status', 'UP')
            ->count();

        $percentage = round(($upLogs / $totalLogs) * 100, 2);
        $this->update(['uptime_percentage' => $percentage]);

        return $percentage;
    }
}
