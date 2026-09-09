<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `placement_test_paragraphs` existed on production (PlacementTestParagraphsModel)
     * but was created directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('placement_test_paragraphs')) {
            return;
        }

        Schema::create('placement_test_paragraphs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('placement_test_section_id');
            $table->longText('paragraph_content');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('placement_test_section_id', 'placement_test_paragraphs_question_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_test_paragraphs');
    }
};
