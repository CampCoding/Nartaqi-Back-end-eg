<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `placement_test_scores` existed on production (PlacementTestStudentScoresModel)
     * but was created directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('placement_test_scores')) {
            return;
        }

        Schema::create('placement_test_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('placement_test_id');
            $table->string('score', 20);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_test_scores');
    }
};
