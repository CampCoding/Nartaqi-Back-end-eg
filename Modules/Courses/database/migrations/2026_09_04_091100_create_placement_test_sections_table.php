<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `placement_test_sections` existed on production (PlacementTestSectionsController/
     * PlacementTestSectionsModel) but was created directly on the server, never
     * through a migration. This is the only legacy table that carries a real FK
     * constraint on production (to `placement_test`), preserved here.
     */
    public function up(): void
    {
        if (Schema::hasTable('placement_test_sections')) {
            return;
        }

        Schema::create('placement_test_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('placement_test_id');
            $table->string('time_if_free', 500)->nullable();
            $table->longText('title');
            $table->longText('description')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('placement_test_id', 'Placement_test_sections_exam_id_foreign');
            $table->foreign('placement_test_id', 'placement_test_sections_placement_test_id_foreign')
                ->references('id')->on('placement_test')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_test_sections');
    }
};
