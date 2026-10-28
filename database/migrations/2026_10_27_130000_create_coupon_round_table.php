<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('coupon_round')) {
            Schema::create('coupon_round', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('coupon_id');
                $table->unsignedBigInteger('round_id');
                $table->timestamps();

                $table->unique(['coupon_id', 'round_id']);
            });
        }

        if (Schema::hasTable('coupons') && Schema::hasColumn('coupons', 'round_id')) {
            $coupons = DB::table('coupons')->whereNotNull('round_id')->get(['id', 'round_id']);
            foreach ($coupons as $coupon) {
                $exists = DB::table('coupon_round')
                    ->where('coupon_id', $coupon->id)
                    ->where('round_id', $coupon->round_id)
                    ->exists();
                if (!$exists) {
                    DB::table('coupon_round')->insert([
                        'coupon_id' => $coupon->id,
                        'round_id' => $coupon->round_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_round');
    }
};
