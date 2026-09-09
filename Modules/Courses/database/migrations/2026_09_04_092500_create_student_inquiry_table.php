<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `student_inquiry` existed on production (StudentinquiryModel) but was
     * created directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('student_inquiry')) {
            return;
        }

        Schema::create('student_inquiry', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 500);
            $table->string('phone', 50);
            $table->string('message_type', 500);
            $table->longText('content');
            $table->enum('solved', ['0', '1']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_inquiry');
    }
};
