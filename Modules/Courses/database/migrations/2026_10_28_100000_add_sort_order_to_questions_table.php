<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-controlled order of questions inside an exam section. Existing rows stay 0, so
     * every query orders by (sort_order, id) and untouched sections keep their old id order.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('questions', 'sort_order')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_active');
                $table->index(['exam_section_id', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('questions', 'sort_order')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropIndex(['exam_section_id', 'sort_order']);
                $table->dropColumn('sort_order');
            });
        }
    }
};
