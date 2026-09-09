<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `course_requirements` existed on production (CourseRequirementsController/
     * CourseRequirementsModel) but was created directly on the server, never
     * through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('course_requirements')) {
            return;
        }

        Schema::create('course_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_requirements');
    }
};
