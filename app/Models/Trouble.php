<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trouble extends Model
{
    /**
     * Get the user that owns the Trouble
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uuid', 'created_by');
    }

    /**
     * Get the confirmed that owns the Trouble
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function confirmed(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uuid', 'confirmed_by');
    }
}
