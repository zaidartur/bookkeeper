<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpAssignments extends Model
{
    /**
     * Get the ip_address that owns the IpAssignments
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ip_address(): BelongsTo
    {
        return $this->belongsTo(IpAddress::class, 'uuid', 'uuid_ip');
    }

    /**
     * Get the user that owns the IpAssignments
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uuid', 'user_id');
    }
}
