<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->string('title');
            $table->string('description');
            $table->string('time_if_free');

            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('exam_sections');
    }
};
