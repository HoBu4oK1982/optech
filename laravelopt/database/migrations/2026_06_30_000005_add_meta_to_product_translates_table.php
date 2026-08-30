<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_translates', function (Blueprint $table) {
            // Перевод мета-тегов товара (раньше переводились только name/description/usage/char)
            $table->string('meta_title_en')->nullable()->after('char_kz');
            $table->string('meta_title_kz')->nullable()->after('meta_title_en');
            $table->string('meta_description_en')->nullable()->after('meta_title_kz');
            $table->string('meta_description_kz')->nullable()->after('meta_description_en');
            $table->string('meta_keywords_en')->nullable()->after('meta_description_kz');
            $table->string('meta_keywords_kz')->nullable()->after('meta_keywords_en');

            $table->string('seo_h1_en')->nullable()->after('meta_keywords_kz');
            $table->string('seo_h1_kz')->nullable()->after('seo_h1_en');

            $table->text('short_description_en')->nullable()->after('seo_h1_kz');
            $table->text('short_description_kz')->nullable()->after('short_description_en');
        });
    }

    public function down(): void
    {
        Schema::table('product_translates', function (Blueprint $table) {
            $table->dropColumn([
                'meta_title_en', 'meta_title_kz',
                'meta_description_en', 'meta_description_kz',
                'meta_keywords_en', 'meta_keywords_kz',
                'seo_h1_en', 'seo_h1_kz',
                'short_description_en', 'short_description_kz',
            ]);
        });
    }
};
