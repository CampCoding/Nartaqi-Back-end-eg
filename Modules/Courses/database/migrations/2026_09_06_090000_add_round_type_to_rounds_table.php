<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distinguishes a normal round (has lessons/content) from an exams-only
     * round (no content, just exams assigned directly via assign_exam_round).
     * Defaults to 'course' so existing rounds and any caller that doesn't
     * send this field keep behaving exactly as before.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('rounds', 'round_type')) {
            Schema::table('rounds', function (Blueprint $table) {
                $table->enum('round_type', ['course', 'exams_only'])->default('course')->after('source');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('rounds', 'round_type')) {
            Schema::table('rounds', function (Blueprint $table) {
                $table->dropColumn('round_type');
            });
        }
    }
};
