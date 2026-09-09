<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `time_show` is already read/written throughout AdminRoundController
     * (round activation check, round copy) but was missing on this
     * environment's database. Other environments already have it manually
     * (as a native TIME column), so guard on its presence and match that type.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('rounds', 'time_show')) {
            Schema::table('rounds', function (Blueprint $table) {
                $table->time('time_show')->nullable()->after('end_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('rounds', 'time_show')) {
            Schema::table('rounds', function (Blueprint $table) {
                $table->dropColumn('time_show');
            });
        }
    }
};
