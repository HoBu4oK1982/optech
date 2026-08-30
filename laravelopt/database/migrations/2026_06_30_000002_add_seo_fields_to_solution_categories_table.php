<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solution_categories', function (Blueprint $table) {
            $table->string('meta_title_en')->nullable()->after('meta_keywords');
            $table->string('meta_title_kz')->nullable()->after('meta_title_en');
            $table->string('meta_description_en')->nullable()->after('meta_title_kz');
            $table->string('meta_description_kz')->nullable()->after('meta_description_en');
            $table->string('meta_keywords_en')->nullable()->after('meta_description_kz');
            $table->string('meta_keywords_kz')->nullable()->after('meta_keywords_en');

            $table->string('seo_h1')->nullable()->after('meta_keywords_kz');
            $table->string('seo_h1_en')->nullable()->after('seo_h1');
            $table->string('seo_h1_kz')->nullable()->after('seo_h1_en');

            $table->longText('seo_text_top')->nullable()->after('seo_h1_kz');
            $table->longText('seo_text_top_en')->nullable()->after('seo_text_top');
            $table->longText('seo_text_top_kz')->nullable()->after('seo_text_top_en');
            $table->longText('seo_text_bottom')->nullable()->after('seo_text_top_kz');
            $table->longText('seo_text_bottom_en')->nullable()->after('seo_text_bottom');
            $table->longText('seo_text_bottom_kz')->nullable()->after('seo_text_bottom_en');

            $table->string('og_image')->nullable()->after('seo_text_bottom_kz');
            $table->string('canonical_url')->nullable()->after('og_image');
            $table->boolean('is_indexable')->default(true)->after('canonical_url');

            $table->string('icon')->nullable()->after('is_indexable');
            $table->integer('sort_order')->default(0)->after('icon');
            $table->json('faq')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('solution_categories', function (Blueprint $table) {
            $table->dropColumn([
                'meta_title_en', 'meta_title_kz',
                'meta_description_en', 'meta_description_kz',
                'meta_keywords_en', 'meta_keywords_kz',
                'seo_h1', 'seo_h1_en', 'seo_h1_kz',
                'seo_text_top', 'seo_text_top_en', 'seo_text_top_kz',
                'seo_text_bottom', 'seo_text_bottom_en', 'seo_text_bottom_kz',
                'og_image', 'canonical_url', 'is_indexable',
                'icon', 'sort_order', 'faq',
            ]);
        });
    }
};
