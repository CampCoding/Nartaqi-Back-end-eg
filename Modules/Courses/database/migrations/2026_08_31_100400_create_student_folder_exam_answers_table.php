<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_folder_exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_exam_id')->constrained('student_folder_exams')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->foreignId('selected_option_id')->nullable()->constrained('question_options')->nullOnDelete();
            $table->text('student_answer')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->unique(['folder_exam_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_folder_exam_answers');
    }
};
