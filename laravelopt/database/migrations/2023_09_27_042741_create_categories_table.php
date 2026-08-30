<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('parent_id')->nullable();
            $table->string('name_en')->nullable();
            $table->text('description_en')->nullable();
            $table->string('name_kz')->nullable();
            $table->text('description_kz')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }

   
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }

    
};
