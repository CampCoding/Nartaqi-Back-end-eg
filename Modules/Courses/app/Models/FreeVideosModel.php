<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Courses\Models\CategoryPartsModel;

use Modules\Courses\Models\CategryPartFreeModel;
// use Modules\Courses\Database\Factories\FreevideosFactory;

class FreeVideosModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    public $timestamps = false;
    protected $table = 'free_videos';
    protected $fillable = [
        'title',
        'description',
        'time',
        'vimeo_link',
        'youtube_link',
        'image',
        'category_part_id',
        'category_part_free_id',
        'sort_number',
    ];
    public $appends = ['image_url'];
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }
        return asset('storage/' . $this->image);
    }
    public function category_part()
    {
        return $this->belongsTo(CategoryPartsModel::class);
    }

    public function category_part_free()
    {
        return $this->belongsTo(CategryPartFreeModel::class, 'category_part_free_id');
    }
}
