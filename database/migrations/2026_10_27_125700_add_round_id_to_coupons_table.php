<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Production already had a coupons table (is_active, target, expiry_date, …)
     * but never got round_id because create_coupons was skipped on an existing table.
     */
    public function up(): void
    {
        if (!Schema::hasTable('coupons') || Schema::hasColumn('coupons', 'round_id')) {
            return;
        }

        Schema::table('coupons', function (Blueprint $table) {
            $table->unsignedBigInteger('round_id')->nullable()->after('target');
        });

        if (Schema::hasTable('rounds')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->foreign('round_id')->references('id')->on('rounds')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('coupons') || !Schema::hasColumn('coupons', 'round_id')) {
            return;
        }

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropForeign(['round_id']);
            $table->dropColumn('round_id');
        });
    }
};
