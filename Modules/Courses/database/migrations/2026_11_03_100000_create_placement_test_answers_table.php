<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The student's answer to each question in one placement test attempt (score_id is the
     * placement_test_scores row of that attempt). option_id is the chosen option, answer_text is for
     * essay questions, is_correct is taken from the option when the answer is saved.
     */
    public function up(): void
    {
        if (Schema::hasTable('placement_test_answers')) {
            return;
        }

        Schema::create('placement_test_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('score_id')->index();
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('option_id')->nullable();
            $table->longText('answer_text')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamps();

            $table->unique(['score_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_test_answers');
    }
};
