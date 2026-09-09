<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `category_parts_for_free` existed on production (CategoryPartFreeController/
     * CategryPartFreeModel) but was created directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('category_parts_for_free')) {
            return;
        }

        Schema::create('category_parts_for_free', function (Blueprint $table) {
            $table->id();
            $table->integer('sort_number');
            $table->string('name');
            $table->string('image', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_parts_for_free');
    }
};
