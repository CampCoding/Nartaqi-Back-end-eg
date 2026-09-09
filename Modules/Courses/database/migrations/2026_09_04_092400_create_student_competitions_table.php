<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `student_competitions` existed on production (StudentCompetitionModel)
     * but was created directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('student_competitions')) {
            return;
        }

        Schema::create('student_competitions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('student_id');
            $table->integer('competition_id');
            $table->timestamp('date_created')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_competitions');
    }
};
