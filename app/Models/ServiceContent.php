<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceContent extends Model
{
    use HasFactory;

    protected $table = 'service_contents';

    protected $fillable = [
        'title',
        'image',
        'content',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
