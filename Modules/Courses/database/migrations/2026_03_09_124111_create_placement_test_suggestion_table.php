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
        Schema::create('placement_test_suggestion', function (Blueprint $table) {
            $table->id();
            $table->integer('from_score');
            $table->integer('to_score');
            $table->text('message')->nullable();
            $table->unsignedBigInteger('suggestion_round_id')->nullable();
            $table->timestamps();

            $table->foreign('suggestion_round_id')->references('id')->on('rounds')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('placement_test_suggestion');
    }
};
