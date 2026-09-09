<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `term_conditions` existed on production (TconditionsController/TconditionsModel)
     * but was created directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('term_conditions')) {
            return;
        }

        Schema::create('term_conditions', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('content');
            $table->enum('type', ['term', 'conditions', 'refund']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_conditions');
    }
};
