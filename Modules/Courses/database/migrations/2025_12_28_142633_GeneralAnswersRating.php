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
        Schema::create("general_answers_ratings", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('general_rating_id');
            $table->string('answer');
            $table->timestamps();

            // Foreign key constraint with cascade delete

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_answers_ratings');
    }
};
