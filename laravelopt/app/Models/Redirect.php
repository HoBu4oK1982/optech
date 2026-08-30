<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    use HasFactory;

    protected $table = 'redirects';

    protected $fillable = [
        'from_url', 'to_url', 'status_code', 'is_active', 'hits', 'last_hit_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'status_code' => 'integer',
        'hits' => 'integer',
        'last_hit_at' => 'datetime',
    ];
}
