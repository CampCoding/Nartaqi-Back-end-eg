<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('assign_exam_round', function (Blueprint $table) {
            $table->id();
            $table->enum('type', allowed: ['full_round', 'lesson']);
           $table->integer(column: 'exam_id');
            $table->integer(column: 'lesson_or_round_id');
             $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('assign_exam_round');
    }
};
