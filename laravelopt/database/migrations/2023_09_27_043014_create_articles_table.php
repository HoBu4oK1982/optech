<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title_ru');
            $table->string('slug')->unique();
            $table->longText('description_ru')->nullable();
            $table->string('title_en')->nullable();
            $table->text('description_en')->nullable();
            $table->string('title_kz')->nullable();
            $table->text('description_kz')->nullable();
            $table->integer('status')->default(0);
            $table->string('image')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
