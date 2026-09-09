<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `competition_question` existed on production (UserCompetitionController /
     * AddCompetitionQuestionRequest) but was created directly on the server,
     * never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('competition_question')) {
            return;
        }

        Schema::create('competition_question', function (Blueprint $table) {
            $table->id();
            $table->integer('competition_id');
            $table->date('show_date')->nullable();
            $table->longText('question_text');
            $table->string('question_type', 600);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_question');
    }
};
