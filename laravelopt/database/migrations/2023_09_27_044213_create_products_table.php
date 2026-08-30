<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('SKU')->nullable(); 
            $table->longText('description')->nullable();
            $table->longText('char')->nullable();
            $table->longText('usage')->nullable();
            $table->string('images')->nullable();
            $table->string('brand_id')->nullable();
            $table->bigInteger('category_id')->unsigned();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }

    

    

    public function down(): void
    {
        Schema::dropIfExists('products');
    }

    
};
