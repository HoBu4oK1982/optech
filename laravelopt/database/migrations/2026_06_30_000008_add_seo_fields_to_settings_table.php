<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // --- Organization / LocalBusiness schema ---
            $table->string('org_legal_name')->nullable()->after('site_name');
            $table->string('bin')->nullable()->after('org_legal_name'); // БИН
            $table->string('geo_lat')->nullable()->after('address');
            $table->string('geo_lng')->nullable()->after('geo_lat');
            $table->json('social_links')->nullable()->after('geo_lng'); // sameAs []

            // --- Дефолтные SEO-шаблоны по языкам ---
            $table->string('default_meta_title')->nullable()->after('social_links');
            $table->string('default_meta_title_en')->nullable()->after('default_meta_title');
            $table->string('default_meta_title_kz')->nullable()->after('default_meta_title_en');
            $table->string('default_meta_description')->nullable()->after('default_meta_title_kz');
            $table->string('default_meta_description_en')->nullable()->after('default_meta_description');
            $table->string('default_meta_description_kz')->nullable()->after('default_meta_description_en');
            $table->string('default_og_image')->nullable()->after('default_meta_description_kz');

            // --- Аналитика и верификации ---
            $table->string('ga4_id')->nullable()->after('default_og_image');
            $table->string('gtm_id')->nullable()->after('ga4_id');
            $table->string('yandex_metrika_id')->nullable()->after('gtm_id');
            $table->string('google_verification')->nullable()->after('yandex_metrika_id');
            $table->string('yandex_verification')->nullable()->after('google_verification');

            // --- Прочее ---
            $table->string('default_locale', 8)->default('ru')->after('yandex_verification');
            $table->text('robots_extra')->nullable()->after('default_locale'); // доп. правила robots.txt
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'org_legal_name', 'bin', 'geo_lat', 'geo_lng', 'social_links',
                'default_meta_title', 'default_meta_title_en', 'default_meta_title_kz',
                'default_meta_description', 'default_meta_description_en', 'default_meta_description_kz',
                'default_og_image',
                'ga4_id', 'gtm_id', 'yandex_metrika_id', 'google_verification', 'yandex_verification',
                'default_locale', 'robots_extra',
            ]);
        });
    }
};
