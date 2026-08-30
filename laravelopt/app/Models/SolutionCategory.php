<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolutionCategory extends Model
{
    use HasFactory;

    protected $table = 'solution_categories';

    protected $fillable = [
        'title_ru', 'slug', 'description_ru',
        'title_en', 'description_en', 'title_kz', 'description_kz',
        'image', 'status',
        'meta_title', 'meta_description', 'meta_keywords',
        'meta_title_en', 'meta_title_kz',
        'meta_description_en', 'meta_description_kz',
        'meta_keywords_en', 'meta_keywords_kz',
        'seo_h1', 'seo_h1_en', 'seo_h1_kz', 'seo_h2',
        'seo_text_top', 'seo_text_top_en', 'seo_text_top_kz',
        'seo_text_bottom', 'seo_text_bottom_en', 'seo_text_bottom_kz',
        'og_image', 'og_title', 'og_description',
        'canonical_url', 'is_indexable', 'is_followable',
        'image_alt', 'image_alt_en', 'image_alt_kz', 'image_title',
        'in_sitemap', 'sitemap_priority', 'sitemap_changefreq',
        'icon', 'sort_order', 'faq',
    ];

    protected $casts = [
        'faq' => 'array',
        'is_indexable' => 'boolean',
        'is_followable' => 'boolean',
        'in_sitemap' => 'boolean',
        'sitemap_priority' => 'decimal:1',
        'sort_order' => 'integer',
    ];

    public function solutions()
    {
        return $this->hasMany(Solution::class, 'category_id');
    }
}
