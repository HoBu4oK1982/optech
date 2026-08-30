<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Приводит схему в полное соответствие с ТЗ New Web (июнь 2026):
     * OG title/description, follow/nofollow, alt/title изображений,
     * настройки sitemap, дополнительный H2, перелинковка, FAQ для статей и т.д.
     * Все добавления идут с проверкой hasColumn — безопасно поверх Step 1.
     */
    private array $contentTables = [
        'categories', 'solution_categories', 'articles', 'products', 'solutions', 'projects',
    ];

    public function up(): void
    {
        // --- Cross-cutting: применяется ко всем контентным сущностям ---
        foreach ($this->contentTables as $t) {
            Schema::table($t, function (Blueprint $table) use ($t) {
                $this->addIfMissing($t, 'og_title',          fn () => $table->string('og_title')->nullable());
                $this->addIfMissing($t, 'og_description',     fn () => $table->string('og_description')->nullable());
                $this->addIfMissing($t, 'is_followable',      fn () => $table->boolean('is_followable')->default(true));
                $this->addIfMissing($t, 'seo_h2',             fn () => $table->string('seo_h2')->nullable());
                // alt важен по языкам для мультиязычного сайта; title — одиночный
                $this->addIfMissing($t, 'image_alt',          fn () => $table->string('image_alt')->nullable());
                $this->addIfMissing($t, 'image_alt_en',       fn () => $table->string('image_alt_en')->nullable());
                $this->addIfMissing($t, 'image_alt_kz',       fn () => $table->string('image_alt_kz')->nullable());
                $this->addIfMissing($t, 'image_title',        fn () => $table->string('image_title')->nullable());
                // sitemap
                $this->addIfMissing($t, 'in_sitemap',         fn () => $table->boolean('in_sitemap')->default(true));
                $this->addIfMissing($t, 'sitemap_priority',   fn () => $table->decimal('sitemap_priority', 2, 1)->default(0.5));
                $this->addIfMissing($t, 'sitemap_changefreq', fn () => $table->string('sitemap_changefreq', 20)->default('weekly'));
            });
        }

        // --- Категории: краткое описание + перелинковка ---
        Schema::table('categories', function (Blueprint $table) {
            $this->addIfMissing('categories', 'short_description',    fn () => $table->text('short_description')->nullable());
            $this->addIfMissing('categories', 'short_description_en', fn () => $table->text('short_description_en')->nullable());
            $this->addIfMissing('categories', 'short_description_kz', fn () => $table->text('short_description_kz')->nullable());
            $this->addIfMissing('categories', 'related_categories',   fn () => $table->json('related_categories')->nullable());
            $this->addIfMissing('categories', 'related_products',     fn () => $table->json('related_products')->nullable());
        });

        // --- Статьи: FAQ, категория статьи, источник, перелинковка ---
        Schema::table('articles', function (Blueprint $table) {
            $this->addIfMissing('articles', 'faq',                fn () => $table->json('faq')->nullable());
            $this->addIfMissing('articles', 'category_id',        fn () => $table->unsignedBigInteger('category_id')->nullable()->index());
            $this->addIfMissing('articles', 'source',             fn () => $table->string('source')->nullable());
            $this->addIfMissing('articles', 'related_products',   fn () => $table->json('related_products')->nullable());
            $this->addIfMissing('articles', 'related_articles',   fn () => $table->json('related_articles')->nullable());
            $this->addIfMissing('articles', 'related_categories', fn () => $table->json('related_categories')->nullable());
        });

        // --- Товары: связанные товары/категории/аналоги ---
        Schema::table('products', function (Blueprint $table) {
            $this->addIfMissing('products', 'related_products',   fn () => $table->json('related_products')->nullable());
            $this->addIfMissing('products', 'related_categories', fn () => $table->json('related_categories')->nullable());
            $this->addIfMissing('products', 'analogs',            fn () => $table->json('analogs')->nullable());
        });

        // --- Решения/Проекты: добавим перелинковку категорий, где её не было ---
        Schema::table('solutions', function (Blueprint $table) {
            $this->addIfMissing('solutions', 'related_categories', fn () => $table->json('related_categories')->nullable());
        });
        Schema::table('projects', function (Blueprint $table) {
            $this->addIfMissing('projects', 'related_categories', fn () => $table->json('related_categories')->nullable());
        });
    }

    public function down(): void
    {
        $cross = [
            'og_title', 'og_description', 'is_followable', 'seo_h2',
            'image_alt', 'image_alt_en', 'image_alt_kz', 'image_title',
            'in_sitemap', 'sitemap_priority', 'sitemap_changefreq',
        ];
        foreach ($this->contentTables as $t) {
            Schema::table($t, function (Blueprint $table) use ($t, $cross) {
                foreach ($cross as $col) {
                    if (Schema::hasColumn($t, $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        $this->dropCols('categories', ['short_description', 'short_description_en', 'short_description_kz', 'related_categories', 'related_products']);
        $this->dropCols('articles', ['faq', 'category_id', 'source', 'related_products', 'related_articles', 'related_categories']);
        $this->dropCols('products', ['related_products', 'related_categories', 'analogs']);
        $this->dropCols('solutions', ['related_categories']);
        $this->dropCols('projects', ['related_categories']);
    }

    private function addIfMissing(string $table, string $column, \Closure $add): void
    {
        if (! Schema::hasColumn($table, $column)) {
            $add();
        }
    }

    private function dropCols(string $table, array $cols): void
    {
        Schema::table($table, function (Blueprint $t) use ($table, $cols) {
            foreach ($cols as $c) {
                if (Schema::hasColumn($table, $c)) {
                    $t->dropColumn($c);
                }
            }
        });
    }
};
