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
        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->string('image');
            $table->string('active');
            $table->float('price');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('gender');
            $table->string('for');
            $table->enum('free', ['0', '1'])->default('0');
            $table->string('goal');
            $table->foreignId('course_category_id')->constrained('course_categories')->onDelete('cascade');
            $table->foreignId('category_part_id')->constrained('category_parts')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->enum('source', ['0', '1'])->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rounds');
    }
};
