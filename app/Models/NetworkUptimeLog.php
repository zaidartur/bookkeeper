<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkUptimeLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'network_monitor_id',
        'status',
        'response_time_ms',
        'checked_at',
    ];

    protected $casts = [
        'response_time_ms' => 'float',
        'checked_at' => 'datetime',
    ];

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(NetworkMonitor::class, 'network_monitor_id');
    }
}
