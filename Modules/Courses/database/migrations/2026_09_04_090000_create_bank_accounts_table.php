<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `bank_accounts` existed on production (BankAccountsController/BankAccountsModel)
     * but was created directly on the server, never through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('bank_accounts')) {
            return;
        }

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('bank_name');
            $table->string('account_holder_name');
            $table->string('account_number', 50);
            $table->string('iban', 50)->nullable();
            $table->string('image')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
