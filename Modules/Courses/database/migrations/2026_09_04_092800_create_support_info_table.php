<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `support_info` existed on production (SupportInfoController/SupportInfoModel)
     * but was created directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('support_info')) {
            return;
        }

        Schema::create('support_info', function (Blueprint $table) {
            $table->increments('id');
            $table->string('whatsapp_number', 30);
            $table->string('whatsapp_message')->nullable();
            $table->boolean('show_whatsapp');
            $table->string('phone_number', 50)->nullable();
            $table->string('support_email')->nullable();
            $table->boolean('show_email');
            $table->text('working_hours_text')->nullable();
            $table->text('response_time_text')->nullable();
            $table->boolean('active');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_info');
    }
};
