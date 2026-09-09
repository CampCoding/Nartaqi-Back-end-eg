<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `question_bank_parts` existed on production (QuestionBankPartsModel) but
     * was created directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('question_bank_parts')) {
            return;
        }

        Schema::create('question_bank_parts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 1000);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank_parts');
    }
};
