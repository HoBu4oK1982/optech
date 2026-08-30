<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('licenses', function (Blueprint $t) {
            if (! Schema::hasColumn('licenses', 'alt')) $t->string('alt')->nullable();
        });
    }
    public function down(): void {
        Schema::table('licenses', function (Blueprint $t) {
            if (Schema::hasColumn('licenses', 'alt')) $t->dropColumn('alt');
        });
    }
};
