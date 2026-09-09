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
        Schema::create('exam_pdfs', function (Blueprint $table) {
            $table->id();
            $table->integer('exam_id');
            $table->string('title');
            $table->string('description');
            $table->string('pdf_url');
            $table->enum('type', ['question', 'answers'])->default('question');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
