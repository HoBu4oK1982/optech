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
    ];

    protected $casts = [
        'faq' => 'array',
        'is_indexable' => 'boolean',
        'is_followable' => 'boolean',
        'in_sitemap' => 'boolean',
        'sitemap_priority' => 'decimal:1',
        'sort_order' => 'integer',
    ];
}
