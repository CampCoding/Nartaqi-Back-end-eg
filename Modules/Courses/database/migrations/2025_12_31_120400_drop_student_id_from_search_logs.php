<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('search_logs', function (Blueprint $table) {
            // Drop foreign key constraint if it exists
            if (Schema::hasColumn('search_logs', 'student_id')) {
                try {
                    $table->dropForeign(['student_id']);
                } catch (\Throwable $e) {
                    // ignore if foreign key doesn't exist
                }
                // Then drop the column
                $table->dropColumn('student_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->nullable()->after('search_query');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('set null');
        });
    }
};
