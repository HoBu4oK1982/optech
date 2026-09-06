<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Тип материала: обычная новость или статья Базы знаний.
 *
 * Раздел «База знаний» на сайте уже есть (/basa-znani), но брать материалы ему
 * было неоткуда: и новости, и SEO-статьи лежат в одной таблице articles без
 * признака, к какому разделу они относятся, а эндпоинтов /knowledge-base на
 * бэкенде не существовало вовсе.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('articles', 'type')) {
            return;
        }

        Schema::table('articles', function (Blueprint $table) {
            $table->string('type', 32)->default('article')->after('slug');
            // Оба списка на сайте выбираются как «тип + опубликованные»,
            // поэтому индекс составной.
            $table->index(['type', 'status'], 'articles_type_status_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('articles', 'type')) {
            return;
        }

        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex('articles_type_status_index');
            $table->dropColumn('type');
        });
    }
};
