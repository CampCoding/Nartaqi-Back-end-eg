<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Some environments already have these columns added manually (no FK,
     * plain int/longtext), so each column is guarded independently instead
     * of assuming a clean starting state.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('questions', 'paragraph_id')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->foreignId('paragraph_id')->nullable()->after('is_active')->constrained('question_paragraphs')->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('questions', 'description')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->text('description')->nullable()->after('paragraph_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'paragraph_id')) {
                $table->dropForeign(['paragraph_id']);
                $table->dropColumn('paragraph_id');
            }
            if (Schema::hasColumn('questions', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
