<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Some environments already have this table added manually (plain int
     * id/round_id, no FK). Guard on its presence so this migration only
     * creates it where it's actually missing.
     */
    public function up(): void
    {
        if (!Schema::hasTable('resource_links')) {
            Schema::create('resource_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('round_id')->constrained('rounds')->cascadeOnDelete();
                $table->string('telegram_link', 500);
                $table->string('whatsapp_link', 500);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_links');
    }
};
