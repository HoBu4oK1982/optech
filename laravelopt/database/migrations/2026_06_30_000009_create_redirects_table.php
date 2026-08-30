<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Менеджер редиректов: критичен при миграции React -> Next, чтобы не терять позиции
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_url')->unique();   // /old-path
            $table->string('to_url');               // /new-path или абсолютный URL
            $table->unsignedSmallInteger('status_code')->default(301); // 301 | 302
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
