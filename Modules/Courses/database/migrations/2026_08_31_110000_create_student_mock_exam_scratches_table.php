<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_mock_exam_scratches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->json('scratch_data');
            $table->timestamps();

            $table->unique(['student_id', 'exam_id', 'question_id'], 'scratch_student_exam_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_mock_exam_scratches');
    }
};
