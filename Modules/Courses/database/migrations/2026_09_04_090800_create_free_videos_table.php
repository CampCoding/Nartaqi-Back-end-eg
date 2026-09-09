<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `free_videos` existed on production (AdminFreeVideosController/FreeVideosModel)
     * but was created directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('free_videos')) {
            return;
        }

        Schema::create('free_videos', function (Blueprint $table) {
            $table->id();
            $table->integer('sort_number');
            $table->integer('category_part_free_id');
            $table->string('title')->nullable();
            $table->string('image', 500)->nullable();
            $table->longText('description')->nullable();
            $table->string('vimeo_link', 500)->nullable();
            $table->string('youtube_link', 500)->nullable();
            $table->string('time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('free_videos');
    }
};
