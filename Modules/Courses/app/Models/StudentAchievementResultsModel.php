<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\StudentAchievementResultsModelFactory;

class StudentAchievementResultsModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'student_achievement_results';

    /**
     * The attributes that are mass assignable.
     */
    public $timestamps = false;
    protected $fillable = [
        'category_part_id',
        'title',
        'image',
        'video_link',
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

    /**
     * Get the category part that owns the achievement result.
     */
    public function category_part()
    {
        return $this->belongsTo(CategoryPartsModel::class, 'category_part_id');
    }

    // protected static function newFactory(): StudentAchievementResultsModelFactory
    // {
    //     // return StudentAchievementResultsModelFactory::new();
    // }
}
