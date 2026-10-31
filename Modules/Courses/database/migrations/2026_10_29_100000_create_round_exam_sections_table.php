<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sections that group a round's whole-round exams (assign_exam_round.type = full_round).
     * An assignment with a null round_exam_section_id is simply not in any section yet.
     */
    public function up(): void
    {
        if (!Schema::hasTable('round_exam_sections')) {
            Schema::create('round_exam_sections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('round_id')->constrained('rounds')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_number')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('assign_exam_round', 'round_exam_section_id')) {
            Schema::table('assign_exam_round', function (Blueprint $table) {
                $table->unsignedBigInteger('round_exam_section_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('assign_exam_round', 'round_exam_section_id')) {
            Schema::table('assign_exam_round', function (Blueprint $table) {
                $table->dropIndex(['round_exam_section_id']);
                $table->dropColumn('round_exam_section_id');
            });
        }

        Schema::dropIfExists('round_exam_sections');
    }
};
