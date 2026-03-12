<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Inventory extends Model
{
    /**
     * Get the category associated with the Inventory
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function category(): HasOne
    {
        return $this->hasOne(Category::class, 'uid', 'uid_category');
    }

    /**
     * Get the brand associated with the Inventory
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function brand(): HasOne
    {
        return $this->hasOne(Brand::class, 'uid', 'uid_brand');
    }

    /**
     * Get the location associated with the Inventory
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function location(): HasOne
    {
        return $this->hasOne(Location::class, 'uid', 'uid_location');
    }

    /**
     * Get the user associated with the Inventory
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'uuid', 'user_id');
    }
}
