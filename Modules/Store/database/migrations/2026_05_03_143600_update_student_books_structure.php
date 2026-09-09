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
        // سأقوم بحذف الجدول القديم وإنشائه بالهيكل الجديد ليدعم تفاصيل الكتب
        Schema::dropIfExists('student_books');
        
        Schema::create('student_books', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('store_id'); // مرجع للعنصر الأصلي
            $table->string('book_name'); // الاسم (مثلاً: كتاب 1)
            $table->string('book_url');  // الرابط المباشر للكتاب
            $table->timestamps();
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
