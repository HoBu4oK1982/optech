<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Расширяет текстовые колонки поиска до LONGTEXT: у товаров встречаются
// очень длинные описания (в т.ч. HTML-таблицы), TEXT (~64КБ) их не вмещает.
// Чистый SQL, чтобы не требовать doctrine/dbal на старых версиях Laravel.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('search_documents')) {
            return;
        }
        foreach (['norm_title', 'norm_all', 'stem_all', 'translit_all'] as $col) {
            DB::statement("ALTER TABLE `search_documents` MODIFY `{$col}` LONGTEXT NULL");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('search_documents')) {
            return;
        }
        foreach (['norm_title', 'norm_all', 'stem_all', 'translit_all'] as $col) {
            DB::statement("ALTER TABLE `search_documents` MODIFY `{$col}` TEXT NULL");
        }
    }
};
