<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardContent extends Model
{
    use HasFactory;

    protected $table = 'card_contents';

    protected $fillable = [
        'title',
        'subtitle',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
