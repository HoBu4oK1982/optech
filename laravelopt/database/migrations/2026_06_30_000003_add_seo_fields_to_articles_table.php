<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Per-language meta
            $table->string('meta_title_en')->nullable()->after('meta_description');
            $table->string('meta_title_kz')->nullable()->after('meta_title_en');
            $table->string('meta_description_en')->nullable()->after('meta_title_kz');
            $table->string('meta_description_kz')->nullable()->after('meta_description_en');
            $table->string('meta_keywords_en')->nullable()->after('meta_description_kz');
            $table->string('meta_keywords_kz')->nullable()->after('meta_keywords_en');

            // H1
            $table->string('seo_h1')->nullable()->after('meta_keywords_kz');
            $table->string('seo_h1_en')->nullable()->after('seo_h1');
            $table->string('seo_h1_kz')->nullable()->after('seo_h1_en');

            // Article schema essentials
            $table->string('author')->nullable()->after('seo_h1_kz');
            $table->timestamp('published_at')->nullable()->after('author');
            $table->unsignedInteger('reading_time')->nullable()->after('published_at');

            // Excerpt / анонс (фолбэк для description + карточки)
            $table->text('excerpt')->nullable()->after('reading_time');
            $table->text('excerpt_en')->nullable()->after('excerpt');
            $table->text('excerpt_kz')->nullable()->after('excerpt_en');

            // Taxonomy для перелинковки
            $table->json('tags')->nullable()->after('excerpt_kz');

            // Social / canonical / robots / sort
            $table->string('og_image')->nullable()->after('tags');
            $table->string('canonical_url')->nullable()->after('og_image');
            $table->boolean('is_indexable')->default(true)->after('canonical_url');
            $table->integer('sort_order')->default(0)->after('is_indexable');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'meta_title_en', 'meta_title_kz',
                'meta_description_en', 'meta_description_kz',
                'meta_keywords_en', 'meta_keywords_kz',
                'seo_h1', 'seo_h1_en', 'seo_h1_kz',
                'author', 'published_at', 'reading_time',
                'excerpt', 'excerpt_en', 'excerpt_kz', 'tags',
                'og_image', 'canonical_url', 'is_indexable', 'sort_order',
            ]);
        });
    }
};
