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
        Schema::create('student_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('exam_id')->constrained('exams');
            $table->integer('score');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('student_scores');
    }
};
