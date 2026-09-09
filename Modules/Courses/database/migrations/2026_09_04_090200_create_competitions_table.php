<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `competitions` existed on production (CompetitionsController/CompetitionsModel)
     * but was created directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('competitions')) {
            return;
        }

        Schema::create('competitions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('competition_name', 500);
            $table->enum('type', ['daily', 'weekly', 'monthly']);
            $table->enum('question_type', ['single', 'multi']);
            $table->longText('idea');
            $table->longText('prize');
            $table->longText('image');
            $table->timestamp('start_date')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('end_date')->nullable();
            $table->enum('active', ['0', '1']);
            $table->timestamp('created_at', 6)->useCurrent();
            $table->timestamp('updated_at', 6)->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
