<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * question_paragraphs is created before any question exists (a paragraph
     * holds many questions via questions.paragraph_id), so question_id can
     * never be populated under the app's actual flow. Some environments
     * already dropped this legacy column manually, so guard on its presence.
     */
    public function up(): void
    {
        if (Schema::hasColumn('question_paragraphs', 'question_id')) {
            DB::statement('ALTER TABLE question_paragraphs MODIFY question_id BIGINT UNSIGNED NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('question_paragraphs', 'question_id')) {
            DB::statement('ALTER TABLE question_paragraphs MODIFY question_id BIGINT UNSIGNED NOT NULL');
        }
    }
};
