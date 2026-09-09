<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `phone` is what AdminsController::login actually authenticates admins
     * by (Admin::where('phone', $phone)), but no migration ever added it —
     * it only exists on environments where it was added manually.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('admins', 'phone')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->string('phone')->after('email')->comment('user_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('admins', 'phone')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->dropColumn('phone');
            });
        }
    }
};
