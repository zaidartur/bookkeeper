<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    use HasFactory;

    protected $table = 'galleries';

    protected $fillable = [
        'title',
        'image',
        'show_title',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_title' => 'boolean',
    ];
}
