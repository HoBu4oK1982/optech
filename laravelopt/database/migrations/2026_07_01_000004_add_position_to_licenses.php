<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('licenses', function (Blueprint $t) {
            if (! Schema::hasColumn('licenses', 'position')) $t->integer('position')->default(0);
        });
    }
    public function down(): void {
        Schema::table('licenses', function (Blueprint $t) {
            if (Schema::hasColumn('licenses', 'position')) $t->dropColumn('position');
        });
    }
};
