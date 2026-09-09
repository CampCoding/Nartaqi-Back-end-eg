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
        // 1. Update store table: Keep only main 'image', drop plural columns and old 'book_url'
        Schema::table('store', function (Blueprint $table) {
            if (Schema::hasColumn('store', 'book_url')) {
                $table->dropColumn('book_url');
            }
            if (Schema::hasColumn('store', 'images')) {
                $table->dropColumn('images');
            }
            if (Schema::hasColumn('store', 'book_urls')) {
                $table->dropColumn('book_urls');
            }
            
            // Ensure hidden is boolean (tinyint)
            $table->boolean('hidden')->default(1)->change();
        });

        // 2. Create store_images table
        Schema::create('store_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('store')->onDelete('cascade');
            $table->string('image');
            $table->timestamps();
        });

        // 3. Create store_books table
        Schema::create('store_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('store')->onDelete('cascade');
            $table->string('book_url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_books');
        Schema::dropIfExists('store_images');
        
        Schema::table('store', function (Blueprint $table) {
            $table->string('book_url')->nullable();
        });
    }
};
