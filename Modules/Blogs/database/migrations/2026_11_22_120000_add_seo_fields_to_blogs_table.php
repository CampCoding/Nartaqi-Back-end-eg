<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Blogs\Models\Blog;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            if (!Schema::hasColumn('blogs', 'slug')) {
                // 191 so the unique index fits on any MySQL/MariaDB row format.
                $table->string('slug', 191)->nullable()->unique()->after('title');
            }
            if (!Schema::hasColumn('blogs', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('content');
            }
            if (!Schema::hasColumn('blogs', 'meta_description')) {
                $table->string('meta_description', 300)->nullable()->after('meta_title');
            }
            if (!Schema::hasColumn('blogs', 'image_alt')) {
                $table->string('image_alt')->nullable()->after('image_cover');
            }
        });

        // Give every existing blog a slug from its title. Written directly so updated_at stays the same.
        DB::table('blogs')->whereNull('slug')->orderBy('id')->select('id', 'title')->get()
            ->each(function ($blog) {
                DB::table('blogs')->where('id', $blog->id)
                    ->update(['slug' => Blog::uniqueSlug((string) $blog->title, $blog->id)]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('blogs', 'slug')) {
            Schema::table('blogs', function (Blueprint $table) {
                $table->dropUnique(['slug']);
            });
        }

        $columns = array_values(array_filter(
            ['slug', 'meta_title', 'meta_description', 'image_alt'],
            fn ($column) => Schema::hasColumn('blogs', $column)
        ));

        if ($columns) {
            Schema::table('blogs', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
