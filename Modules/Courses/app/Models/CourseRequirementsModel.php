<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\CourseRequirementsModelFactory;

class CourseRequirementsModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'course_requirements';
    protected $fillable = [
        'question',
        'answer',
    ];

    // protected static function newFactory(): CourseRequirementsModelFactory
    // {
    //     // return CourseRequirementsModelFactory::new();
    // }
}
