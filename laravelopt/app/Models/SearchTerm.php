<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchTerm extends Model
{
    protected $table = 'search_terms';
    protected $fillable = ['term', 'translit', 'locale', 'df', 'popularity'];
}
