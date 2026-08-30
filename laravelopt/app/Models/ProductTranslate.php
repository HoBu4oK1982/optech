<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTranslate extends Model
{
    use HasFactory;

    protected $table = 'product_translates';

    protected $fillable = [
        'product_id',
        'name_en', 'description_en', 'char_en', 'usage_en',
        'name_kz', 'description_kz', 'char_kz', 'usage_kz',
        // Meta (EN/KZ)
        'meta_title_en', 'meta_title_kz',
        'meta_description_en', 'meta_description_kz',
        'meta_keywords_en', 'meta_keywords_kz',
        'seo_h1_en', 'seo_h1_kz',
        'short_description_en', 'short_description_kz',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
