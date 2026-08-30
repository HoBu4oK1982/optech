<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // --- Per-language meta (RU остаётся в meta_*; EN/KZ кладём в product_translates) ---
            // H1 (RU тут, EN/KZ в product_translates)
            $table->string('seo_h1')->nullable()->after('meta_keywords');

            // --- Commerce / Product schema (КРИТИЧНО: сейчас цены нет -> Offer невалиден) ---
            $table->decimal('price', 12, 2)->nullable()->after('seo_h1');
            $table->decimal('old_price', 12, 2)->nullable()->after('price');
            $table->string('currency', 8)->default('KZT')->after('old_price');
            $table->boolean('price_on_request')->default(false)->after('currency');
            // InStock | OutOfStock | PreOrder | BackOrder
            $table->string('availability')->default('InStock')->after('price_on_request');

            // --- Идентификаторы для Merchant / Product schema ---
            $table->string('gtin')->nullable()->after('availability');
            $table->string('mpn')->nullable()->after('gtin');

            // --- Короткое описание (RU; EN/KZ в product_translates) ---
            $table->text('short_description')->nullable()->after('mpn');

            // --- Медиа: галерея, документы (даташиты), видео ---
            $table->json('gallery')->nullable()->after('short_description');
            $table->json('documents')->nullable()->after('gallery');
            $table->string('video_url')->nullable()->after('documents');

            // --- FAQ (FAQ schema) ---
            $table->json('faq')->nullable()->after('video_url');

            // --- Отзывы (AggregateRating schema) ---
            $table->decimal('rating', 3, 2)->nullable()->after('faq');
            $table->unsignedInteger('reviews_count')->default(0)->after('rating');

            // --- Social / canonical / robots / sort ---
            $table->string('og_image')->nullable()->after('reviews_count');
            $table->string('canonical_url')->nullable()->after('og_image');
            $table->boolean('is_indexable')->default(true)->after('canonical_url');
            $table->integer('sort_order')->default(0)->after('is_indexable');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'seo_h1',
                'price', 'old_price', 'currency', 'price_on_request', 'availability',
                'gtin', 'mpn', 'short_description',
                'gallery', 'documents', 'video_url', 'faq',
                'rating', 'reviews_count',
                'og_image', 'canonical_url', 'is_indexable', 'sort_order',
            ]);
        });
    }
};
