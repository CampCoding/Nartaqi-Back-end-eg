<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `competition_question_answers` existed on production but was created
     * directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('competition_question_answers')) {
            return;
        }

        Schema::create('competition_question_answers', function (Blueprint $table) {
            $table->id();
            $table->integer('student_id');
            $table->unsignedBigInteger('question_id');
            $table->integer('competition_id');
            $table->text('answer_text')->nullable();
            $table->unsignedBigInteger('correct_or_not')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('question_id', 'competition_question_answers_question_id_foreign');
            $table->index('correct_or_not', 'competition_question_answers_correct_option_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_question_answers');
    }
};
