<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-controlled lock on the videos of lesson trainings (for_type = lesson) and round exams
     * (for_type = exam). New videos start locked until the admin opens them; videos that already
     * existed stay open so nothing students can watch today disappears.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('exam_videos', 'is_locked')) {
            Schema::table('exam_videos', function (Blueprint $table) {
                $table->boolean('is_locked')->default(true);
            });

            DB::table('exam_videos')->update(['is_locked' => false]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('exam_videos', 'is_locked')) {
            Schema::table('exam_videos', function (Blueprint $table) {
                $table->dropColumn('is_locked');
            });
        }
    }
};
