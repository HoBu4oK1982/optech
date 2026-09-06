<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $table = 'brands';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'status',
        'meta_title', 'meta_description', 'meta_keywords',
        'meta_title_en', 'meta_title_kz',
        'meta_description_en', 'meta_description_kz',
        'meta_keywords_en', 'meta_keywords_kz',
        'seo_h1', 'seo_h1_en', 'seo_h1_kz', 'seo_h2',
        'og_image', 'og_title', 'og_description',
        'canonical_url', 'is_indexable', 'is_followable',
        'image_alt', 'image_alt_en', 'image_alt_kz', 'image_title',
        'in_sitemap', 'sitemap_priority', 'sitemap_changefreq',
        'sort_order', 'faq',
        // Страница бренда — листинг товаров, как категория: свои SEO-тексты
        // над и под сеткой и отдельный заголовок в хлебных крошках.
        'seo_text_top', 'seo_text_top_en', 'seo_text_top_kz',
        'seo_text_bottom', 'seo_text_bottom_en', 'seo_text_bottom_kz',
        'breadcrumb_title',
    ];

    protected $casts = [
        'faq' => 'array',
        'is_indexable' => 'boolean',
        'is_followable' => 'boolean',
        'in_sitemap' => 'boolean',
        'sitemap_priority' => 'decimal:1',
        'sort_order' => 'integer',
    ];

    public function product(){
        return $this->hasMany(Product::class);
    }
}
