<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `question_bank_paragraphs` existed on production (QuestionBankParagraphModel)
     * but was created directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('question_bank_paragraphs')) {
            return;
        }

        Schema::create('question_bank_paragraphs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_bank_skills_id');
            $table->longText('paragraph_content');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('question_bank_skills_id', 'question_bank_paragraphs_question_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank_paragraphs');
    }
};
