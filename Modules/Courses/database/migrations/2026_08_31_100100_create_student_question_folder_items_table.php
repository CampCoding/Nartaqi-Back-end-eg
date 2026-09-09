<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_question_folder_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('student_question_folders')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['folder_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_question_folder_items');
    }
};
