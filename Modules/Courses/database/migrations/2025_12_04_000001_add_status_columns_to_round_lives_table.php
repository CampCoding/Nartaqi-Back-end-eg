<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('round_lives')) {
            return;
        }

        Schema::table('round_lives', function (Blueprint $table) {
            if (! Schema::hasColumn('round_lives', 'active')) {
                $table->boolean('active')
                    ->default(1)
                    ->after('date');
            }

            if (! Schema::hasColumn('round_lives', 'finished')) {
                $table->boolean('finished')
                    ->default(0)
                    ->after('active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('round_lives')) {
            return;
        }

        Schema::table('round_lives', function (Blueprint $table) {
            if (Schema::hasColumn('round_lives', 'finished')) {
                $table->dropColumn('finished');
            }

            if (Schema::hasColumn('round_lives', 'active')) {
                $table->dropColumn('active');
            }
        });
    }
};







