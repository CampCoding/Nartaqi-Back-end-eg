<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The bank exam a copy was made from (null for bank exams and for copies made before this
     * column existed). Lets the same bank exam be recognised when it is added to a round/lesson again.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('exams', 'copied_from_id')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->unsignedBigInteger('copied_from_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('exams', 'copied_from_id')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->dropIndex(['copied_from_id']);
                $table->dropColumn('copied_from_id');
            });
        }
    }
};
