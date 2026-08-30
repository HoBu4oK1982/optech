<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solution extends Model
{
    use HasFactory;

    protected $table = 'solutions';

    protected $fillable = [
        'title_ru', 'slug', 'category_id', 'description_ru',
        'title_en', 'description_en', 'title_kz', 'description_kz',
        'image', 'status',
        'meta_title', 'meta_keywords', 'meta_description',
        'meta_title_en', 'meta_title_kz',
        'meta_description_en', 'meta_description_kz',
        'meta_keywords_en', 'meta_keywords_kz',
        'seo_h1', 'seo_h1_en', 'seo_h1_kz', 'seo_h2',
        'gallery', 'faq', 'related_products', 'related_solutions', 'related_categories',
        'og_image', 'og_title', 'og_description',
        'canonical_url', 'is_indexable', 'is_followable', 'is_featured',
        'image_alt', 'image_alt_en', 'image_alt_kz', 'image_title',
        'in_sitemap', 'sitemap_priority', 'sitemap_changefreq',
        'sort_order',
    ];

    protected $casts = [
        'gallery' => 'array',
        'faq' => 'array',
        'related_products' => 'array',
        'related_solutions' => 'array',
        'related_categories' => 'array',
        'is_indexable' => 'boolean',
        'is_followable' => 'boolean',
        'in_sitemap' => 'boolean',
        'is_featured' => 'boolean',
        'sitemap_priority' => 'decimal:1',
        'sort_order' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(SolutionCategory::class);
    }
}
