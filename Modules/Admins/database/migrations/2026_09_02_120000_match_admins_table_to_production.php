<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Production still has the legacy `role` column and never got
     * `permissions` (it only ran the role_id migration, not the two after
     * it). The app is tied to production's frontend, so this environment's
     * schema is kept matching production exactly rather than the newer
     * role_id/permissions design the later migrations moved toward.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('admins', 'role')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->enum('role', ['superadmin', 'employee'])->nullable()->after('token');
            });
        }

        if (Schema::hasColumn('admins', 'permissions')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->dropColumn('permissions');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('admins', 'role')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }

        if (!Schema::hasColumn('admins', 'permissions')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->text('permissions')->nullable();
            });
        }
    }
};
