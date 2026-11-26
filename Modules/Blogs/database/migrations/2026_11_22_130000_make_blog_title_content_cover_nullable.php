<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every add_blog / update_blog field is optional, so a blog can be saved without a title,
     * content or cover image.
     */
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->string('title')->nullable()->change();
            $table->text('content')->nullable()->change();
            $table->string('image_cover')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('blogs')->whereNull('title')->update(['title' => '']);
        DB::table('blogs')->whereNull('content')->update(['content' => '']);
        DB::table('blogs')->whereNull('image_cover')->update(['image_cover' => '']);

        Schema::table('blogs', function (Blueprint $table) {
            $table->string('title')->nullable(false)->change();
            $table->text('content')->nullable(false)->change();
            $table->string('image_cover')->nullable(false)->change();
        });
    }
};
