<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deleting a question from an exam now only trashes it (deleted_at), so the admin can see where it
     * was deleted from and restore it. deleted_by is the admin who deleted it.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('questions', 'deleted_at')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->softDeletes()->index();
                $table->unsignedBigInteger('deleted_by')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('questions', 'deleted_at')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropIndex(['deleted_at']);
                $table->dropColumn(['deleted_at', 'deleted_by']);
            });
        }
    }
};
