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
        Schema::create('student_books', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id'); // يربط بجدول الطلاب/المستخدمين
            $table->foreignId('store_id')->constrained('store')->onDelete('cascade'); // يربط بجدول الكتب
            $table->timestamps();
            
            // لضمان عدم تكرار نفس الكتاب لنفس الطالب
            $table->unique(['student_id', 'store_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_books');
    }
};
