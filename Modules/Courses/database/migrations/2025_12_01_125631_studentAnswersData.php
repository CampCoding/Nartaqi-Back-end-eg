<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('student_answers_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('exam_id')->constrained('exams');
            // One row per question, with separated columns
            $table->unsignedBigInteger('question_id');
            $table->enum('type', ['mcq', 'paragraph']);

            // For MCQ: can store option id or index (cast to int in model if needed)
            // For paragraph: store the full text answer
            $table->text('student_answer')->nullable();

            // Optional: store the correct answer (option id/index or reference text)
            $table->text('correct_answer')->nullable();

            // Whether this student's answer is correct (nullable before grading)
            $table->boolean('is_correct')->nullable();

            $table->timestamps();
        });
    }


    public function down(): void {
        Schema::dropIfExists('student_answers_data');
    }
};
