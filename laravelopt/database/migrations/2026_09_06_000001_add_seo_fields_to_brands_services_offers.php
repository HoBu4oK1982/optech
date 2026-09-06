<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SEO-поля для брендов, услуг и спецпредложений.
 *
 * Это три последние сущности сайта, у которых из всего SEO были только
 * meta_title / meta_description / meta_keywords на русском — ни переводов,
 * ни H1, ни canonical, ни управления индексацией и sitemap. У категорий,
 * товаров, статей, решений и проектов этот набор появился ещё в миграциях
 * 2026_06_30_*, здесь он доводится до того же вида.
 *
 * Все колонки добавляются с проверкой hasColumn: миграция должна безопасно
 * применяться на базе, где часть полей уже могла быть заведена руками.
 */
return new class extends Migration
{
    /** Общий набор SEO-полей для всех трёх таблиц. */
    private function commonColumns(Blueprint $table, string $tableName): void
    {
        $add = function (string $column, callable $definition) use ($table, $tableName) {
            if (! Schema::hasColumn($tableName, $column)) {
                $definition($table);
            }
        };

        // Meta по языкам (русские meta_* уже есть во всех трёх таблицах).
        foreach (['meta_title', 'meta_description', 'meta_keywords'] as $base) {
            foreach (['_en', '_kz'] as $suffix) {
                $add($base . $suffix, fn ($t) => $t->string($base . $suffix)->nullable());
            }
        }

        // Заголовки страницы.
        foreach (['seo_h1', 'seo_h1_en', 'seo_h1_kz', 'seo_h2'] as $column) {
            $add($column, fn ($t) => $t->string($column)->nullable());
        }

        // Open Graph.
        $add('og_image', fn ($t) => $t->string('og_image')->nullable());
        $add('og_title', fn ($t) => $t->string('og_title')->nullable());
        $add('og_description', fn ($t) => $t->text('og_description')->nullable());

        // Canonical и управление индексацией.
        $add('canonical_url', fn ($t) => $t->string('canonical_url')->nullable());
        $add('is_indexable', fn ($t) => $t->boolean('is_indexable')->default(true));
        $add('is_followable', fn ($t) => $t->boolean('is_followable')->default(true));

        // Alt/title изображений.
        foreach (['image_alt', 'image_alt_en', 'image_alt_kz', 'image_title'] as $column) {
            $add($column, fn ($t) => $t->string($column)->nullable());
        }

        // Sitemap.
        $add('in_sitemap', fn ($t) => $t->boolean('in_sitemap')->default(true));
        $add('sitemap_priority', fn ($t) => $t->decimal('sitemap_priority', 2, 1)->default(0.6));
        $add('sitemap_changefreq', fn ($t) => $t->string('sitemap_changefreq')->default('weekly'));

        $add('sort_order', fn ($t) => $t->integer('sort_order')->default(0));
        $add('faq', fn ($t) => $t->json('faq')->nullable());
    }

    public function up(): void
    {
        foreach (['brands', 'services', 'special_offers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $this->commonColumns($table, $tableName);
            });
        }

        // Страница бренда — это листинг товаров, как категория: ей нужны
        // текстовые SEO-блоки над и под сеткой и свой заголовок в крошках.
        Schema::table('brands', function (Blueprint $table) {
            $add = function (string $column, callable $definition) use ($table) {
                if (! Schema::hasColumn('brands', $column)) {
                    $definition($table);
                }
            };
            foreach ([
                'seo_text_top', 'seo_text_top_en', 'seo_text_top_kz',
                'seo_text_bottom', 'seo_text_bottom_en', 'seo_text_bottom_kz',
            ] as $column) {
                $add($column, fn ($t) => $t->longText($column)->nullable());
            }
            $add('breadcrumb_title', fn ($t) => $t->string('breadcrumb_title')->nullable());
        });
    }

    public function down(): void
    {
        $common = [
            'meta_title_en', 'meta_title_kz',
            'meta_description_en', 'meta_description_kz',
            'meta_keywords_en', 'meta_keywords_kz',
            'seo_h1', 'seo_h1_en', 'seo_h1_kz', 'seo_h2',
            'og_image', 'og_title', 'og_description',
            'canonical_url', 'is_indexable', 'is_followable',
            'image_alt', 'image_alt_en', 'image_alt_kz', 'image_title',
            'in_sitemap', 'sitemap_priority', 'sitemap_changefreq',
            'faq',
        ];

        foreach (['brands', 'services', 'special_offers'] as $tableName) {
            // sort_order у brands завёлся отдельной миграцией 2026_07_01_000000,
            // откатывать его здесь нельзя.
            $columns = $tableName === 'brands' ? $common : array_merge($common, ['sort_order']);

            if ($tableName === 'brands') {
                $columns = array_merge($columns, [
                    'seo_text_top', 'seo_text_top_en', 'seo_text_top_kz',
                    'seo_text_bottom', 'seo_text_bottom_en', 'seo_text_bottom_kz',
                    'breadcrumb_title',
                ]);
            }

            $existing = array_values(array_filter(
                $columns,
                fn ($column) => Schema::hasColumn($tableName, $column)
            ));

            if ($existing) {
                Schema::table($tableName, function (Blueprint $table) use ($existing) {
                    $table->dropColumn($existing);
                });
            }
        }
    }
};
