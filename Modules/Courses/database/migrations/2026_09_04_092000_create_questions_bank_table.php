<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `questions_bank` existed on production (QuestionsBankController/QuestionsBankModel)
     * but was created directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('questions_bank')) {
            return;
        }

        Schema::create('questions_bank', function (Blueprint $table) {
            $table->id();
            $table->integer('question_bank_skills_id');
            $table->enum('type', ['mcq', 'paragraph']);
            $table->integer('paragraph_id')->nullable();
            $table->longText('question_text');
            $table->string('question_type', 600);
            $table->string('instructions', 500)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions_bank');
    }
};
