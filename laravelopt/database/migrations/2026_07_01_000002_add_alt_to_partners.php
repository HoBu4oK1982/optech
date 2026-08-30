<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('partners', function (Blueprint $t) {
            if (! Schema::hasColumn('partners', 'alt')) $t->string('alt')->nullable();
        });
    }
    public function down(): void {
        Schema::table('partners', function (Blueprint $t) {
            if (Schema::hasColumn('partners', 'alt')) $t->dropColumn('alt');
        });
    }
};
