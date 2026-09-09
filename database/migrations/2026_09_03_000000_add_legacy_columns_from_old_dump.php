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
        Schema::table('assign_exam_round', function (Blueprint $table) {
            $table->date('show_date')->nullable()->after('lesson_or_round_id');
            $table->bigInteger('sort_number')->nullable()->after('show_date');
        });

        Schema::table('category_parts', function (Blueprint $table) {
            $table->string('image', 500)->nullable()->after('name');
            $table->integer('sort_number')->default(0)->after('course_category_id');
            $table->integer('sort_as_student_degree')->default(1)->after('sort_number');
            $table->integer('sort_as_placement_test')->default(1)->after('sort_as_student_degree');
        });

        Schema::table('exam_pdfs', function (Blueprint $table) {
            $table->enum('for_type', ['lesson', 'exam'])->nullable()->after('id');
        });

        Schema::table('exam_videos', function (Blueprint $table) {
            $table->enum('for_type', ['lesson', 'exam'])->nullable()->after('id');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->enum('type', ['complaints', 'general_faqs'])->nullable()->after('answer');
        });

        Schema::table('placement_test_suggestion', function (Blueprint $table) {
            $table->integer('placement_test_id')->nullable()->after('id');
        });

        Schema::table('rounds', function (Blueprint $table) {
            $table->enum('show_round_book', ['0', '1'])->default('0')->after('free');
            $table->enum('have_certificate', ['0', '1'])->nullable()->after('goal');
            $table->integer('category_part_free_id')->nullable()->after('course_category_id');
            $table->string('capacity', 500)->nullable()->after('category_part_id');
            $table->string('round_book', 500)->nullable()->after('time_show');
            $table->string('round_road_map_book', 500)->nullable()->after('round_book');
        });

        Schema::table('round_contents', function (Blueprint $table) {
            $table->date('show_date')->nullable()->after('description');
            $table->enum('type', ['basic', 'lecture'])->nullable()->after('show_date');
            $table->bigInteger('sort_number')->nullable()->after('type');
        });

        Schema::table('round_lives', function (Blueprint $table) {
            $table->string('meeting_id', 500)->nullable()->after('title');
            $table->string('password', 500)->nullable()->after('link');
            $table->string('end_time', 20)->nullable()->after('time');
        });

        Schema::table('round_resources', function (Blueprint $table) {
            $table->date('show_date')->nullable()->after('url');
        });

        Schema::table('student_certificates', function (Blueprint $table) {
            $table->string('certification_name')->nullable()->after('round_id');
        });

        Schema::table('student_rates', function (Blueprint $table) {
            $table->enum('hidden', ['0', '1'])->default('0')->after('comment');
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->string('vimeo_link', 500)->nullable()->after('description');
            $table->string('youtube_link', 500)->nullable()->after('vimeo_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assign_exam_round', function (Blueprint $table) {
            $table->dropColumn(['show_date', 'sort_number']);
        });

        Schema::table('category_parts', function (Blueprint $table) {
            $table->dropColumn(['image', 'sort_number', 'sort_as_student_degree', 'sort_as_placement_test']);
        });

        Schema::table('exam_pdfs', function (Blueprint $table) {
            $table->dropColumn('for_type');
        });

        Schema::table('exam_videos', function (Blueprint $table) {
            $table->dropColumn('for_type');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('placement_test_suggestion', function (Blueprint $table) {
            $table->dropColumn('placement_test_id');
        });

        Schema::table('rounds', function (Blueprint $table) {
            $table->dropColumn([
                'show_round_book',
                'have_certificate',
                'category_part_free_id',
                'capacity',
                'round_book',
                'round_road_map_book',
            ]);
        });

        Schema::table('round_contents', function (Blueprint $table) {
            $table->dropColumn(['show_date', 'type', 'sort_number']);
        });

        Schema::table('round_lives', function (Blueprint $table) {
            $table->dropColumn(['meeting_id', 'password', 'end_time']);
        });

        Schema::table('round_resources', function (Blueprint $table) {
            $table->dropColumn('show_date');
        });

        Schema::table('student_certificates', function (Blueprint $table) {
            $table->dropColumn('certification_name');
        });

        Schema::table('student_rates', function (Blueprint $table) {
            $table->dropColumn('hidden');
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['vimeo_link', 'youtube_link']);
        });
    }
};
