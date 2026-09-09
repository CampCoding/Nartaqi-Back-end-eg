<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `placement_test_questions` existed on production (PlacementTestQuestionsController/
     * PlacementTestQuestionsModel) but was created directly on the server, never
     * through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('placement_test_questions')) {
            return;
        }

        Schema::create('placement_test_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('placement_test_section_id');
            $table->longText('question_text');
            $table->enum('question_type', ['t_f', 'essay', 'mcq', 'paragraph_mcq'])->default('mcq');
            $table->integer('paragraph_id')->nullable();
            $table->string('instructions', 500)->nullable();
            $table->text('label')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('placement_test_section_id', 'placement_test_questions_exam_section_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_test_questions');
    }
};
