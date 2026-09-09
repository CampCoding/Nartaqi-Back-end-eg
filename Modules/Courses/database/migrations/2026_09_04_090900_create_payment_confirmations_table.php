<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `payment_confirmations` existed on production (PaymentConfirmationsController/
     * PaymentConfirmationsModel) but was created directly on the server, never
     * through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('payment_confirmations')) {
            return;
        }

        Schema::create('payment_confirmations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('student_id');
            $table->integer('round_id');
            $table->string('phone', 20)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('sender_name')->nullable();
            $table->string('receiver_bank', 500);
            $table->longText('image');
            $table->enum('status', ['pending', 'approved', 'rejected'])->nullable()->default('pending');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_confirmations');
    }
};
