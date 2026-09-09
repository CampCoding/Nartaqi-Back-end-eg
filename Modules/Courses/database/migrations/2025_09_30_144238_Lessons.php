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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_content_id')->constrained('round_contents');
            $table->string('title');
            $table->string('description');
            $table->enum('type', ['basic', 'lecture']);
            $table->date('show_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
