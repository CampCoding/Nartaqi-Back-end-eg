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
        Schema::create('marketers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('token')->nullable();
            $table->string('national_id')->unique();
            $table->string('city');
            $table->string('description');
            $table->string('cv');
            $table->string('account_owner');
            $table->string('account_number');
            $table->string('iban_number');
            $table->string('whatsapp_number');
            $table->enum('status', ['pending', 'approved', 'suspended'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketers');
    }
};
