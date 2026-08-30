<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = "categories";

    protected $fillable = [
        'name', 'slug', 'description', 'image',
        'name_en', 'description_en', 'name_kz', 'description_kz',
        'parent_id', 'status',
        'short_description', 'short_description_en', 'short_description_kz',
        // SEO (RU/EN/KZ)
        'meta_title', 'meta_description', 'meta_keywords',
        'meta_title_en', 'meta_title_kz',
        'meta_description_en', 'meta_description_kz',
        'meta_keywords_en', 'meta_keywords_kz',
        'seo_h1', 'seo_h1_en', 'seo_h1_kz', 'seo_h2',
        'seo_text_top', 'seo_text_top_en', 'seo_text_top_kz',
        'seo_text_bottom', 'seo_text_bottom_en', 'seo_text_bottom_kz',
        // OG / canonical / robots
        'og_image', 'og_title', 'og_description',
        'canonical_url', 'is_indexable', 'is_followable',
        // Изображения alt/title
        'image_alt', 'image_alt_en', 'image_alt_kz', 'image_title',
        // Sitemap
        'in_sitemap', 'sitemap_priority', 'sitemap_changefreq',
        // UX / перелинковка
        'breadcrumb_title', 'icon', 'sort_order', 'faq',
        'related_categories', 'related_products',
    ];

    protected $casts = [
        'faq' => 'array',
        'related_categories' => 'array',
        'related_products' => 'array',
        'is_indexable' => 'boolean',
        'is_followable' => 'boolean',
        'in_sitemap' => 'boolean',
        'sitemap_priority' => 'decimal:1',
        'sort_order' => 'integer',
    ];

    public function product()
    {
        return $this->hasMany(Product::class);
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
}
