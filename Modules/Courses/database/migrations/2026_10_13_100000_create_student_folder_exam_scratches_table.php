<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Folder exams live in student_folder_exams, so their scratch pads can't use
    // student_mock_exam_scratches (its exam_id is a foreign key to exams).
    public function up(): void
    {
        if (Schema::hasTable('student_folder_exam_scratches')) {
            return;
        }

        Schema::create('student_folder_exam_scratches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_exam_id')->constrained('student_folder_exams')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->json('scratch_data');
            $table->timestamps();

            $table->unique(['folder_exam_id', 'question_id'], 'folder_scratch_exam_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_folder_exam_scratches');
    }
};
