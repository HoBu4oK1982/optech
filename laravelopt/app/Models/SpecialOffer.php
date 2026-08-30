<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialOffer extends Model
{
    use HasFactory;

    protected $table = 'special_offers';

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
