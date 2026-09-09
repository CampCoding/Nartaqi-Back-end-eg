<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The original create_admins_table migration declared `id` as a plain
     * unsignedBigInteger with no PRIMARY KEY / AUTO_INCREMENT, so InnoDB
     * silently promoted the `email` unique index as the clustering key
     * instead. Production already has a proper id primary key; this only
     * fixes environments built from the old, broken migration.
     */
    public function up(): void
    {
        $hasPrimaryOnId = DB::selectOne(
            "SELECT COUNT(*) AS c FROM information_schema.KEY_COLUMN_USAGE
             WHERE table_schema = DATABASE() AND table_name = 'admins'
               AND column_name = 'id' AND constraint_name = 'PRIMARY'"
        )->c > 0;

        if (!$hasPrimaryOnId) {
            DB::statement('ALTER TABLE admins MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left as a no-op: removing the primary key would
        // re-introduce the original bug and isn't something we want to undo.
    }
};
