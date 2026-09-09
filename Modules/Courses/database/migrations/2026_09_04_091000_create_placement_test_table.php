<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `placement_test` existed on production (PlacementTestController/PlacementTestModel)
     * but was created directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('placement_test')) {
            return;
        }

        Schema::create('placement_test', function (Blueprint $table) {
            $table->id();
            $table->integer('category_part_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_test');
    }
};
