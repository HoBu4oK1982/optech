<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'name', 'slug', 'SKU', 'char', 'usage', 'image', 'images',
        'category_id', 'brand_id', 'file', 'status', 'description',
        'meta_title', 'meta_keywords', 'meta_description',
        'seo_h1', 'seo_h2',
        // Commerce
        'price', 'old_price', 'currency', 'price_on_request', 'availability',
        'gtin', 'mpn', 'short_description',
        // Media
        'gallery', 'documents', 'video_url', 'faq',
        // Reviews
        'rating', 'reviews_count',
        // OG / canonical / robots
        'og_image', 'og_title', 'og_description',
        'canonical_url', 'is_indexable', 'is_followable',
        'image_alt', 'image_alt_en', 'image_alt_kz', 'image_title',
        'in_sitemap', 'sitemap_priority', 'sitemap_changefreq',
        'sort_order',
        // Перелинковка / аналоги
        'related_products', 'related_categories', 'analogs',
    ];

    protected $casts = [
        'gallery' => 'array',
        'documents' => 'array',
        'faq' => 'array',
        'related_products' => 'array',
        'related_categories' => 'array',
        'analogs' => 'array',
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'price_on_request' => 'boolean',
        'is_indexable' => 'boolean',
        'is_followable' => 'boolean',
        'in_sitemap' => 'boolean',
        'sitemap_priority' => 'decimal:1',
        'reviews_count' => 'integer',
        'sort_order' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function translate()
    {
        return $this->hasOne(ProductTranslate::class);
    }
}
