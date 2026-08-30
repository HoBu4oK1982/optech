<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('product_translates', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id')->unique();
            $table->string('name_en')->nullable();
            $table->string('name_kz')->nullable();
            $table->mediumText('description_en')->nullable();
            $table->mediumText('description_kz')->nullable();
            $table->mediumText('usage_en')->nullable();
            $table->mediumText('usage_kz')->nullable();
            $table->mediumText('char_en')->nullable();
            $table->mediumText('char_kz')->nullable();
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('product_translates');
    }

};
