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
        'content',
        'image',
        'image_cover',
        'published_at',
        'hidden',
        'related_blogs_ids',
        'views',
    ];

    protected $casts = [
        'related_blogs_ids' => 'array',
    ];

    public function relatedBlogs()
    {
        $ids = $this->related_blogs_ids ?: [];
        if (is_string($ids)) {
            $ids = array_filter(explode(',', $ids));
        }

        $ids = array_map('intval', (array)$ids);

        return Blog::whereIn('id', $ids)->where('hidden', 0)->get();
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
