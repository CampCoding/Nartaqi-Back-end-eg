<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Courses\Models\Coupon;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Coupon for a specific Round
        Coupon::create([
            'code' => 'COURSE50',
            'type' => 'fixed',
            'value' => 50.00,
            'target' => 'rounds',
            'round_id' => 5, // Replace with a valid round_id from your DB
            'usage_limit' => 100,
            'used_count' => 0,
            'expiry_date' => '2026-12-31',
            'is_active' => true
        ]);

        // 2. Coupon for the whole Store (Cart)
        Coupon::create([
            'code' => 'STORE10',
            'type' => 'percentage',
            'value' => 10.00,
            'target' => 'store',
            'round_id' => null,
            'usage_limit' => 50,
            'used_count' => 0,
            'expiry_date' => '2026-12-31',
            'is_active' => true
        ]);

        // 3. General Fixed Discount for Store
        Coupon::create([
            'code' => 'WELCOME20',
            'type' => 'fixed',
            'value' => 20.00,
            'target' => 'store',
            'round_id' => null,
            'usage_limit' => 200,
            'used_count' => 0,
            'expiry_date' => '2026-12-31',
            'is_active' => true
        ]);
    }
}
