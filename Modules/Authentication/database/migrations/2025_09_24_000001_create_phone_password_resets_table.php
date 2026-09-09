<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('phone_password_resets', function (Blueprint $table): void {
            $table->id();
            $table->string('phone')->index();
            $table->string('code');
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamps();
            $table->unique(['phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_password_resets');
    }
};


