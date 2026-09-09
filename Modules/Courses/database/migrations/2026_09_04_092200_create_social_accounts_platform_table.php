<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `social_accounts_platform` existed on production (SocialAccountsModel/
     * UpdateSocialAccountRequest) but was created directly on the server, never
     * through a migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('social_accounts_platform')) {
            return;
        }

        Schema::create('social_accounts_platform', function (Blueprint $table) {
            $table->increments('id');
            $table->string('platform_name', 50)->nullable();
            $table->string('platform_link')->nullable();
            $table->longText('image')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts_platform');
    }
};
