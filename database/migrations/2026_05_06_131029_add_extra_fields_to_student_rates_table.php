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
        Schema::table('student_rates', function (Blueprint $table) {
            $table->string('recommend_to_friends')->nullable();
            $table->string('moderator_interaction')->nullable();
            $table->string('response_speed')->nullable();
            $table->string('platform_ease_of_use')->nullable();
            $table->string('notifications_rating')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_rates', function (Blueprint $table) {
            $table->dropColumn([
                'recommend_to_friends',
                'moderator_interaction',
                'response_speed',
                'platform_ease_of_use',
                'notifications_rating'
            ]);
        });
    }
};
