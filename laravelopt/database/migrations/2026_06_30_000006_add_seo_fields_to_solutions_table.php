<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solutions', function (Blueprint $table) {
            $table->string('meta_title_en')->nullable()->after('meta_description');
            $table->string('meta_title_kz')->nullable()->after('meta_title_en');
            $table->string('meta_description_en')->nullable()->after('meta_title_kz');
            $table->string('meta_description_kz')->nullable()->after('meta_description_en');
            $table->string('meta_keywords_en')->nullable()->after('meta_description_kz');
            $table->string('meta_keywords_kz')->nullable()->after('meta_keywords_en');

            $table->string('seo_h1')->nullable()->after('meta_keywords_kz');
            $table->string('seo_h1_en')->nullable()->after('seo_h1');
            $table->string('seo_h1_kz')->nullable()->after('seo_h1_en');

            // Медиа + контентные блоки
            $table->json('gallery')->nullable()->after('seo_h1_kz');
            $table->json('faq')->nullable()->after('gallery');

            // Внутренняя перелинковка
            $table->json('related_products')->nullable()->after('faq');
            $table->json('related_solutions')->nullable()->after('related_products');

            $table->string('og_image')->nullable()->after('related_solutions');
            $table->string('canonical_url')->nullable()->after('og_image');
            $table->boolean('is_indexable')->default(true)->after('canonical_url');
            $table->boolean('is_featured')->default(false)->after('is_indexable');
            $table->integer('sort_order')->default(0)->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('solutions', function (Blueprint $table) {
            $table->dropColumn([
                'meta_title_en', 'meta_title_kz',
                'meta_description_en', 'meta_description_kz',
                'meta_keywords_en', 'meta_keywords_kz',
                'seo_h1', 'seo_h1_en', 'seo_h1_kz',
                'gallery', 'faq', 'related_products', 'related_solutions',
                'og_image', 'canonical_url', 'is_indexable', 'is_featured', 'sort_order',
            ]);
        });
    }
};
