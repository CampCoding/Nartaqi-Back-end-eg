<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\CourseCategoriesFactory;

class CourseCategories extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    public $table = 'course_categories';
    protected $fillable = [
        'name',
        'description',
        // 'image',
        'active',
        'show_date',
        'sort_number',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // public $appends = ['image_url'];
    // public function getImageUrlAttribute()
    // {
    //     return url('storage/' . $this->image);
    // }

    // public function free_videos()
    // {
    //     return $this->hasMany(FreeVideosModel::class, 'course_category_id');
    // }
    public function rounds()
    {
        return $this->hasMany(Rounds::class, 'course_category_id');
    }
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }
    public function lessons()
    {
        return $this->hasMany(LessonsModel::class, 'course_category_id');
    }
    public function category_parts()
    {
        return $this->hasMany(CategoryPartsModel::class, 'course_category_id');
    }


    // protected static function newFactory(): CourseCategoriesFactory
    // {
    //     // return CourseCategoriesFactory::new();
    // }
}
