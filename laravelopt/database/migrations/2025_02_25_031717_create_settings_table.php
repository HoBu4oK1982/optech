<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('logo')->nullable();
            $table->string('site_name')->nullable();
            $table->string('slogan')->nullable();
            $table->string('map')->nullable();
            $table->string('copyright')->nullable();
            $table->string('year')->nullable();
            $table->string('work_time')->nullable();
            $table->string('phone')->nullable();
            $table->string('city_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
