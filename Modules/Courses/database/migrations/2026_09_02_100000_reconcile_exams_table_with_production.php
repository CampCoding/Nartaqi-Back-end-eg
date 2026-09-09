<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original create-table migration for `exams` didn't match what the
     * app actually uses (AdminExamModel's $fillable, StoreExamRequest) or
     * what production really has: `exam_type`/`round_id`/`lesson_id` were
     * never real/used, and `type`/`from_copy`/`level`/`success_percentage`/
     * `exam_label_id` were missing. This reconciles any environment built
     * from the old migration; it's a no-op on a database that already
     * matches production.
     */
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            foreach (['exam_type', 'round_id', 'lesson_id'] as $column) {
                if (Schema::hasColumn('exams', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('exams', function (Blueprint $table) {
            if (!Schema::hasColumn('exams', 'exam_label_id')) {
                $table->integer('exam_label_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('exams', 'type')) {
                $table->enum('type', ['intern', 'mock'])->after('title');
            }
            if (!Schema::hasColumn('exams', 'from_copy')) {
                $table->enum('from_copy', ['0', '1'])->default('0')->after('type');
            }
            if (!Schema::hasColumn('exams', 'level')) {
                $table->string('level', 50)->after('free');
            }
            if (!Schema::hasColumn('exams', 'success_percentage')) {
                $table->string('success_percentage', 20)->default('50')->after('time');
            }
        });

        DB::statement('ALTER TABLE exams MODIFY description TEXT NULL');
        DB::statement('ALTER TABLE exams MODIFY time VARCHAR(255) NULL');
        DB::statement('ALTER TABLE exams MODIFY date VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            foreach (['exam_label_id', 'type', 'from_copy', 'level', 'success_percentage'] as $column) {
                if (Schema::hasColumn('exams', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
