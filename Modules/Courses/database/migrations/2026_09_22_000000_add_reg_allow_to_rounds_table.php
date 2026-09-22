<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (! Schema::hasColumn('rounds', 'reg_allow')) {
            Schema::table('rounds', function (Blueprint $table) {
                $table->boolean('reg_allow')->default(1)->after('active');
            });
        }
    }
    public function down(): void {
        if (Schema::hasColumn('rounds', 'reg_allow')) {
            Schema::table('rounds', function (Blueprint $table) {
                $table->dropColumn('reg_allow');
            });
        }
    }
};
