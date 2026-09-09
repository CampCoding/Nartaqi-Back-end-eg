<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\CategoryPartsModelFactory;

class CategoryPartsModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    public $table = 'category_parts';
    public $timestamps = false;
    const CREATED_AT = null;
    const UPDATED_AT = null;
    protected $fillable = [
        'name',
        'course_category_id',
        'image',
        'sort_number',
        'sort_as_placement_test',
        'sort_as_student_degree'
    ];
    public $appends = ['image_url'];
    public function getImageUrlAttribute()
    {
        return url('storage/' . $this->image);
    }
    public function course_categories()
    {
        return $this->belongsTo(CourseCategories::class, 'course_category_id');
    }

    public function rounds()
    {
        return $this->hasMany(Rounds::class, 'category_part_id');
    }
    public function free_videos()
    {
        return $this->hasMany(FreeVideosModel::class, 'category_part_id');
    }

    public function student_achievement_results()
    {
        return $this->hasMany(StudentAchievementResultsModel::class, 'category_part_id');
    }

    public function placement_test()
    {
        return $this->hasOne(PlacementTestModel::class, 'category_part_id');
    }

    // protected static function newFactory(): CategoryPartsModelFactory
    // {
    //     // return CategoryPartsModelFactory::new();
    // }
}
