<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cidr extends Model
{
    /**
     * Get all of the ip_address for the Cidr
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ip_address(): HasMany
    {
        return $this->hasMany(IpAddress::class, 'cidr', 'cidr');
    }
}
