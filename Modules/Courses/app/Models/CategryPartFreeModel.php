<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\CategryPartFreeModelFactory;

class CategryPartFreeModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'category_parts_for_free';

    /**
     * The attributes that are mass assignable.
     */
    public $timestamps = false;
    protected $fillable = [
        'name',
        'image',
        'sort_number'
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['image_url'];

    /**
     * Get the full URL for the image.
     */
    public function getImageUrlAttribute()
    {
        return $this->image ? url('storage/' . $this->image) : null;
    }

    public function free_videos()
    {
        return $this->hasMany(FreeVideosModel::class, 'category_part_free_id');
    }

    // protected static function newFactory(): CategryPartFreeModelFactory
    // {
    //     // return CategryPartFreeModelFactory::new();
    // }
}
