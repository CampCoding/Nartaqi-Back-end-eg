<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `student_achievement_results` existed on production
     * (StudentAchievementResultsController/StudentAchievementResultsModel) but
     * was created directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('student_achievement_results')) {
            return;
        }

        Schema::create('student_achievement_results', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('sort_number');
            $table->integer('category_part_id');
            $table->string('title', 500)->nullable();
            $table->string('image', 500);
            $table->string('video_link', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_achievement_results');
    }
};
