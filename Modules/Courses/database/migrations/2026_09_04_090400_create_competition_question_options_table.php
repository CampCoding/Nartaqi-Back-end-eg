<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `competition_question_options` existed on production but was created
     * directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('competition_question_options')) {
            return;
        }

        Schema::create('competition_question_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->longText('option_text');
            $table->boolean('is_correct')->default(false);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('question_id', 'competition_question_options_question_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_question_options');
    }
};
