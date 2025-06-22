<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IpAddress extends Model
{
    /**
     * Get all of the ip_assignment for the IpAddress
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ip_assignment(): HasMany
    {
        return $this->hasMany(IpAssignments::class, 'uuid_ip', 'uuid');
    }

    /**
     * Get the cidr that owns the IpAddress
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cidr(): BelongsTo
    {
        return $this->belongsTo(Cidr::class, 'cidr', 'cidr');
    }

    /**
     * Get the user that owns the IpAddress
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'uuid');
    }
}
