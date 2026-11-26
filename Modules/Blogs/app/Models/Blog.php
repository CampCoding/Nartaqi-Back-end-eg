<?php

namespace Modules\Blogs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Blogs\Database\Factories\BlogFactory;

class Blog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'image',
        'image_cover',
        'image_alt',
        'published_at',
        'hidden',
        'related_blogs_ids',
        'views',
    ];

    protected $casts = [
        'related_blogs_ids' => 'array',
    ];

    /** Light columns for blog cards (related blogs). */
    public const CARD_COLUMNS = ['id', 'slug', 'title', 'image', 'image_cover', 'image_alt', 'published_at', 'created_at'];

    protected static function booted(): void
    {
        // A blog saved without a slug gets one from its title. An existing slug is never changed here,
        // so editing the title keeps the blog's link. A blog with no title yet gets its slug once a
        // title is added (instead of a meaningless "blog-3" that would then stay forever).
        static::saving(function (Blog $blog) {
            if (blank($blog->slug) && filled($blog->title)) {
                $blog->slug = static::uniqueSlug((string) $blog->title, $blog->id);
            }
        });
    }

    /**
     * Readable link text: Arabic and Latin letters and digits joined by "-".
     * Str::slug drops Arabic letters, so it is built by hand. Diacritics and tatweel are removed.
     * A slug that is only a number gets a "blog-" prefix so it is never mistaken for an id.
     */
    public static function normalizeSlug(?string $text, int $maxLength = 80): string
    {
        $text = mb_strtolower(trim((string) $text));
        $text = (string) preg_replace('/[\p{Mn}\x{0640}]+/u', '', $text);
        $text = (string) preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
        $text = trim(mb_substr(trim($text, '-'), 0, $maxLength), '-');

        if ($text !== '' && is_numeric($text)) {
            $text = 'blog-' . $text;
        }

        return $text;
    }

    /** A slug for the title that no other blog uses ("-2", "-3"... added on repeats). */
    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = static::normalizeSlug($title) ?: 'blog';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    /** Visible and already published (no publish date, or a date that has passed). */
    public function scopePublished($query)
    {
        return $query->where($query->qualifyColumn('hidden'), 0)
            ->where(fn ($q) => $q->whereNull($q->qualifyColumn('published_at'))
                ->orWhere($q->qualifyColumn('published_at'), '<=', now()));
    }

    /** Related blogs in the order the admin chose, published only, card fields only (no content). */
    public function relatedBlogs()
    {
        $ids = $this->related_blogs_ids ?: [];
        if (is_string($ids)) {
            $ids = array_filter(explode(',', $ids));
        }

        $ids = array_values(array_diff(
            array_unique(array_filter(array_map('intval', (array) $ids))),
            [(int) $this->id]
        ));

        if (!$ids) {
            return collect();
        }

        return Blog::published()
            ->whereIn('id', $ids)
            ->select(self::CARD_COLUMNS)
            ->get()
            ->sortBy(fn ($blog) => array_search((int) $blog->id, $ids, true))
            ->values();
    }



    protected $appends = ['image_url', 'image_cover_url'];
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return asset('storage/' . ltrim($this->image, '/'));
    }

    public function getImageCoverUrlAttribute()
    {
        if (!$this->image_cover) {
            return null;
        }

        if (str_starts_with($this->image_cover, 'http://') || str_starts_with($this->image_cover, 'https://')) {
            return $this->image_cover;
        }

        return asset('storage/' . ltrim($this->image_cover, '/'));
    }

    public function comments()
    {
        return $this->hasMany(BlogComment::class, 'blog_id', 'id');
    }

    // protected static function newFactory(): BlogFactory
    // {
    //     // return BlogFactory::new();
    // }
}
