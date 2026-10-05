<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpAssignments extends Model
{
    protected $fillable = [
        'uuid_ip',
        'assigned_ip',
        'device',
        'kategori',
        'status',
        'mac_address',
        'hostname',
        'source',
        'last_seen',
        'keterangan',
        'user_id',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
    ];

    /**
     * Get the ip_address that owns the IpAssignments
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ip_address(): BelongsTo
    {
        return $this->belongsTo(IpAddress::class, 'uuid_ip', 'uuid');
    }

    /**
     * Get the user that owns the IpAssignments
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'uuid');
    }
}
