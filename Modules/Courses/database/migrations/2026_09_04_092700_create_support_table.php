<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `support` existed on production (referenced alongside SupportInfoController)
     * but was created directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('support')) {
            return;
        }

        Schema::create('support', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('title');
            $table->longText('youtube_link');
            $table->longText('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support');
    }
};
