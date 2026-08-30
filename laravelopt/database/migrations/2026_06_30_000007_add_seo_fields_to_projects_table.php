<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('meta_title_en')->nullable()->after('meta_description');
            $table->string('meta_title_kz')->nullable()->after('meta_title_en');
            $table->string('meta_description_en')->nullable()->after('meta_title_kz');
            $table->string('meta_description_kz')->nullable()->after('meta_description_en');
            $table->string('meta_keywords_en')->nullable()->after('meta_description_kz');
            $table->string('meta_keywords_kz')->nullable()->after('meta_keywords_en');

            $table->string('seo_h1')->nullable()->after('meta_keywords_kz');
            $table->string('seo_h1_en')->nullable()->after('seo_h1');
            $table->string('seo_h1_kz')->nullable()->after('seo_h1_en');

            // Данные кейса
            $table->string('client')->nullable()->after('seo_h1_kz');
            $table->string('project_year')->nullable()->after('client');
            $table->string('location')->nullable()->after('project_year');
            $table->json('result_metrics')->nullable()->after('location'); // [{label, value}]

            // Медиа + перелинковка
            $table->json('gallery')->nullable()->after('result_metrics');
            $table->json('related_solutions')->nullable()->after('gallery');
            $table->json('related_products')->nullable()->after('related_solutions');

            $table->string('og_image')->nullable()->after('related_products');
            $table->string('canonical_url')->nullable()->after('og_image');
            $table->boolean('is_indexable')->default(true)->after('canonical_url');
            $table->integer('sort_order')->default(0)->after('is_indexable');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'meta_title_en', 'meta_title_kz',
                'meta_description_en', 'meta_description_kz',
                'meta_keywords_en', 'meta_keywords_kz',
                'seo_h1', 'seo_h1_en', 'seo_h1_kz',
                'client', 'project_year', 'location', 'result_metrics',
                'gallery', 'related_solutions', 'related_products',
                'og_image', 'canonical_url', 'is_indexable', 'sort_order',
            ]);
        });
    }
};
