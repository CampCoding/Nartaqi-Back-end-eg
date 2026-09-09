<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `exam_labels` existed on production (referenced by exams.exam_label_id,
     * see 2026_09_02_100000_reconcile_exams_table_with_production) but was
     * created directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('exam_labels')) {
            return;
        }

        Schema::create('exam_labels', function (Blueprint $table) {
            $table->increments('id');
            $table->string('label', 500);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_labels');
    }
};
