<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `student_views` existed on production (StudentView model) but was
     * created directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('student_views')) {
            return;
        }

        Schema::create('student_views', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('student_id');
            $table->integer('round_id');
            $table->integer('video_id');
            $table->timestamp('created_at', 6)->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_views');
    }
};
