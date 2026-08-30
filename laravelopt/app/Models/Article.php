<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $table = 'articles';

    protected $fillable = [
        'title_ru', 'slug', 'description_ru',
        'title_en', 'description_en', 'title_kz', 'description_kz',
        'status', 'image', 'category_id',
        'meta_title', 'meta_description', 'meta_keywords',
        'meta_title_en', 'meta_title_kz',
        'meta_description_en', 'meta_description_kz',
        'meta_keywords_en', 'meta_keywords_kz',
        'seo_h1', 'seo_h1_en', 'seo_h1_kz', 'seo_h2',
        'author', 'source', 'published_at', 'reading_time',
        'excerpt', 'excerpt_en', 'excerpt_kz', 'tags', 'faq',
        'og_image', 'og_title', 'og_description',
        'canonical_url', 'is_indexable', 'is_followable',
        'image_alt', 'image_alt_en', 'image_alt_kz', 'image_title',
        'in_sitemap', 'sitemap_priority', 'sitemap_changefreq',
        'sort_order',
        'related_products', 'related_articles', 'related_categories',
    ];

    protected $casts = [
        'tags' => 'array',
        'faq' => 'array',
        'related_products' => 'array',
        'related_articles' => 'array',
        'related_categories' => 'array',
        'published_at' => 'datetime',
        'is_indexable' => 'boolean',
        'is_followable' => 'boolean',
        'in_sitemap' => 'boolean',
        'sitemap_priority' => 'decimal:1',
        'reading_time' => 'integer',
        'sort_order' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
