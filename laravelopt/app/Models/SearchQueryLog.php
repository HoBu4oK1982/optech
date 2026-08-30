<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchQueryLog extends Model
{
    protected $table = 'search_queries';
    public $timestamps = false;

    protected $fillable = [
        'query', 'normalized', 'locale', 'results_count',
        'clicked_type', 'clicked_id', 'ip', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];
}
