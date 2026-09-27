<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (! Schema::hasColumn('rounds', 'intro_video')) {
            Schema::table('rounds', function (Blueprint $table) {
                $table->text('intro_video')->nullable()->default(null)->after('description');
            });
        }
    }
    public function down(): void {
        if (Schema::hasColumn('rounds', 'intro_video')) {
            Schema::table('rounds', function (Blueprint $table) {
                $table->dropColumn('intro_video');
            });
        }
    }
};
