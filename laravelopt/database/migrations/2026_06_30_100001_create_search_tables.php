<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_documents', function (Blueprint $table) {
            $table->id();
            $table->string('searchable_type');
            $table->unsignedBigInteger('searchable_id');
            $table->string('locale', 5)->default('ru');
            $table->string('type', 20)->index();

            $table->string('title');
            $table->string('url')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 8)->nullable();
            $table->boolean('in_stock')->default(false);
            $table->float('popularity')->default(0);
            $table->float('weight')->default(1);

            $table->text('norm_title')->nullable();
            $table->text('norm_all')->nullable();
            $table->text('stem_all')->nullable();
            $table->text('translit_all')->nullable();
            $table->json('tokens')->nullable();
            $table->json('translit')->nullable();

            $table->timestamps();

            $table->unique(['searchable_type', 'searchable_id', 'locale'], 'search_doc_unique');
            $table->index(['locale', 'type']);
        });

        Schema::create('search_terms', function (Blueprint $table) {
            $table->id();
            $table->string('term');
            $table->string('translit')->nullable();
            $table->string('locale', 5)->default('ru');
            $table->unsignedInteger('df')->default(0);          // document frequency
            $table->unsignedInteger('popularity')->default(0);   // из логов запросов
            $table->timestamps();

            $table->unique(['term', 'locale']);
            $table->index(['locale', 'df']);
        });

        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->string('query');
            $table->string('normalized')->nullable()->index();
            $table->string('locale', 5)->default('ru');
            $table->unsignedInteger('results_count')->default(0);
            $table->string('clicked_type')->nullable();
            $table->unsignedBigInteger('clicked_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
        Schema::dropIfExists('search_terms');
        Schema::dropIfExists('search_documents');
    }
};
