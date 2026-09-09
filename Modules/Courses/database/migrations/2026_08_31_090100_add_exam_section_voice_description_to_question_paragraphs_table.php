<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Some environments already have exam_section_id/voice added manually
     * (no FK, plain bigint/longtext) but are missing `description` (the
     * shared paragraph description). Each column is guarded independently.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('question_paragraphs', 'exam_section_id')) {
            Schema::table('question_paragraphs', function (Blueprint $table) {
                $table->foreignId('exam_section_id')->nullable()->after('question_id')->constrained('exam_sections')->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('question_paragraphs', 'voice')) {
            Schema::table('question_paragraphs', function (Blueprint $table) {
                $table->string('voice')->nullable()->after('paragraph_content');
            });
        }

        if (!Schema::hasColumn('question_paragraphs', 'description')) {
            Schema::table('question_paragraphs', function (Blueprint $table) {
                $table->text('description')->nullable()->after('voice');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_paragraphs', function (Blueprint $table) {
            if (Schema::hasColumn('question_paragraphs', 'exam_section_id')) {
                try {
                    $table->dropForeign(['exam_section_id']);
                } catch (\Throwable $e) {
                    // no FK to drop on environments where the column was added manually
                }
                $table->dropColumn('exam_section_id');
            }
            if (Schema::hasColumn('question_paragraphs', 'voice')) {
                $table->dropColumn('voice');
            }
            if (Schema::hasColumn('question_paragraphs', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
