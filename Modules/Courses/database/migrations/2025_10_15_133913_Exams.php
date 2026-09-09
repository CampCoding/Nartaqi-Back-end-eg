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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->integer('exam_label_id')->nullable();
            $table->string('title');
            $table->enum('type', ['intern', 'mock']);
            $table->enum('from_copy', ['0', '1'])->default('0');
            $table->text('description')->nullable();
            $table->string('free')->default('0');
            $table->string('level', 50);
            $table->string('time')->nullable();
            $table->string('success_percentage', 20)->default('50');
            $table->string('date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
