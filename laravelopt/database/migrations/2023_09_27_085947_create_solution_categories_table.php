<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('solution_categories', function (Blueprint $table) {
            $table->id();
            $table->string('title_ru')->unique();
            $table->string('slug')->unique();
            $table->text('description_ru')->nullable();
            $table->string('image')->nullable();
            $table->string('title_en')->nullable();
            $table->text('description_en')->nullable();
            $table->string('title_kz')->nullable();
            $table->text('description_kz')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solution_categories');
    }
};
