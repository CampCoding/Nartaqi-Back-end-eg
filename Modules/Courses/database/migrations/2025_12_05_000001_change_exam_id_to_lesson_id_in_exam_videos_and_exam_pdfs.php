<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change exam_id to lesson_id in exam_videos table
        if (Schema::hasColumn('exam_videos', 'exam_id')) {
            Schema::table('exam_videos', function (Blueprint $table) {
                // Check if there's a foreign key constraint and drop it
                $foreignKeys = \DB::select(
                    "SELECT CONSTRAINT_NAME 
                     FROM information_schema.KEY_COLUMN_USAGE 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'exam_videos' 
                     AND COLUMN_NAME = 'exam_id' 
                     AND REFERENCED_TABLE_NAME IS NOT NULL"
                );

                foreach ($foreignKeys as $foreignKey) {
                    $table->dropForeign([$foreignKey->CONSTRAINT_NAME]);
                }

                $table->dropColumn('exam_id');
            });
        }

        if (!Schema::hasColumn('exam_videos', 'lesson_id')) {
            Schema::table('exam_videos', function (Blueprint $table) {
                $table->foreignId('lesson_id')->after('id')->constrained('lessons')->onDelete('cascade');
            });
        }

        // Change exam_id to lesson_id in exam_pdfs table
        if (Schema::hasColumn('exam_pdfs', 'exam_id')) {
            Schema::table('exam_pdfs', function (Blueprint $table) {
                // Check if there's a foreign key constraint and drop it
                $foreignKeys = \DB::select(
                    "SELECT CONSTRAINT_NAME 
                     FROM information_schema.KEY_COLUMN_USAGE 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'exam_pdfs' 
                     AND COLUMN_NAME = 'exam_id' 
                     AND REFERENCED_TABLE_NAME IS NOT NULL"
                );

                foreach ($foreignKeys as $foreignKey) {
                    $table->dropForeign([$foreignKey->CONSTRAINT_NAME]);
                }

                $table->dropColumn('exam_id');
            });
        }

        if (!Schema::hasColumn('exam_pdfs', 'lesson_id')) {
            Schema::table('exam_pdfs', function (Blueprint $table) {
                $table->foreignId('lesson_id')->after('id')->constrained('lessons')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert exam_videos
        if (Schema::hasColumn('exam_videos', 'lesson_id')) {
            Schema::table('exam_videos', function (Blueprint $table) {
                $table->dropForeign(['lesson_id']);
                $table->dropColumn('lesson_id');
            });
        }

        if (!Schema::hasColumn('exam_videos', 'exam_id')) {
            Schema::table('exam_videos', function (Blueprint $table) {
                $table->integer('exam_id')->after('id');
            });
        }

        // Revert exam_pdfs
        if (Schema::hasColumn('exam_pdfs', 'lesson_id')) {
            Schema::table('exam_pdfs', function (Blueprint $table) {
                $table->dropForeign(['lesson_id']);
                $table->dropColumn('lesson_id');
            });
        }

        if (!Schema::hasColumn('exam_pdfs', 'exam_id')) {
            Schema::table('exam_pdfs', function (Blueprint $table) {
                $table->integer('exam_id')->after('id');
            });
        }
    }
};
