<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('search_documents')) {
            return;
        }

        Schema::table('search_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('search_documents', 'norm_strict')) {
                $table->longText('norm_strict')->nullable()->after('norm_title');
            }
            if (! Schema::hasColumn('search_documents', 'stem_strict')) {
                $table->longText('stem_strict')->nullable()->after('norm_all');
            }
            if (! Schema::hasColumn('search_documents', 'translit_strict')) {
                $table->longText('translit_strict')->nullable()->after('stem_all');
            }
            if (! Schema::hasColumn('search_documents', 'token_fields')) {
                $table->json('token_fields')->nullable()->after('tokens');
            }
            if (! Schema::hasColumn('search_documents', 'translit_fields')) {
                $table->json('translit_fields')->nullable()->after('translit');
            }
            if (! Schema::hasColumn('search_documents', 'strict_tokens')) {
                $table->json('strict_tokens')->nullable()->after('translit_fields');
            }
            if (! Schema::hasColumn('search_documents', 'strict_translit')) {
                $table->json('strict_translit')->nullable()->after('strict_tokens');
            }
            if (! Schema::hasColumn('search_documents', 'title_tokens')) {
                $table->json('title_tokens')->nullable()->after('strict_translit');
            }
            if (! Schema::hasColumn('search_documents', 'keyword_tokens')) {
                $table->json('keyword_tokens')->nullable()->after('title_tokens');
            }
            if (! Schema::hasColumn('search_documents', 'body_tokens')) {
                $table->json('body_tokens')->nullable()->after('keyword_tokens');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('search_documents')) {
            return;
        }

        Schema::table('search_documents', function (Blueprint $table) {
            foreach ([
                'body_tokens', 'keyword_tokens', 'title_tokens', 'strict_translit', 'strict_tokens',
                'translit_fields', 'token_fields', 'translit_strict', 'stem_strict', 'norm_strict',
            ] as $column) {
                if (Schema::hasColumn('search_documents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
