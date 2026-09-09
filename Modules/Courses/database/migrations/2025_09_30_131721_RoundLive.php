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
        Schema::create('round_lives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lesson_id')->nullable();
            $table->string('title');
            $table->string('link');
            $table->string('time');
            $table->string('date');
             $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
