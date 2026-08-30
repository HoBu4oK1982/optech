<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';

    protected $fillable = [
        'title_ru',
        'slug',
        'description_ru',
        'title_en',
        'description_en',
        'title_kz',
        'description_kz',
        'image',
        'status',
        'meta_title',
        'meta_keywords',
        'meta_description',
    ];
}
