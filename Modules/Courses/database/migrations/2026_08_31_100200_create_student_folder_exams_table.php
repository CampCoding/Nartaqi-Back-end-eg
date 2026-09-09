<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_folder_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('folder_id')->constrained('student_question_folders')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('mode')->default('default');
            $table->unsignedInteger('question_count');
            $table->string('status')->default('created');
            $table->unsignedInteger('score')->nullable();
            $table->unsignedTinyInteger('percentage')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_folder_exams');
    }
};
