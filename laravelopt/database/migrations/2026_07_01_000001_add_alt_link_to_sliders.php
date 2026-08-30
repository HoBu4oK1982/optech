<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sliders', function (Blueprint $table) {
            if (! Schema::hasColumn('sliders', 'alt'))  $table->string('alt')->nullable();
            if (! Schema::hasColumn('sliders', 'link')) $table->string('link')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sliders', function (Blueprint $table) {
            if (Schema::hasColumn('sliders', 'alt'))  $table->dropColumn('alt');
            if (Schema::hasColumn('sliders', 'link')) $table->dropColumn('link');
        });
    }
};
