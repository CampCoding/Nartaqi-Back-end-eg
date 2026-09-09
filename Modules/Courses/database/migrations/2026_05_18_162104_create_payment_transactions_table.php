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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_id')->nullable()->unique();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->string('currency')->default('EGP');
            $table->string('status')->default('pending'); // pending, paid, failed
            $table->string('type')->default('single'); // single, cart
            $table->unsignedBigInteger('round_id')->nullable();
            $table->text('pay_load')->nullable();
            $table->longText('raw_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
