<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dynamic QR codes for printed books: book -> sections -> codes. A printed code holds only
     * {qr.base_url}/{serial}; where it leads (target_url) can be changed at any time.
     */
    public function up(): void
    {
        if (!Schema::hasTable('qr_books')) {
            Schema::create('qr_books', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('qr_sections')) {
            Schema::create('qr_sections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('qr_book_id')->constrained('qr_books')->cascadeOnDelete();
                $table->string('title');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('qr_codes')) {
            Schema::create('qr_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('section_id')->constrained('qr_sections')->cascadeOnDelete();
                $table->string('serial', 32)->unique();
                $table->string('name');
                $table->text('target_url')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('qr_scans')) {
            Schema::create('qr_scans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('qr_code_id')->constrained('qr_codes')->cascadeOnDelete();
                $table->timestamp('scanned_at')->useCurrent();
                $table->string('user_agent', 500)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_scans');
        Schema::dropIfExists('qr_codes');
        Schema::dropIfExists('qr_sections');
        Schema::dropIfExists('qr_books');
    }
};
