<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Authentication\Models\Student;

// use Modules\Courses\Database\Factories\StudentViewFactory;

class StudentView extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'student_views';
    public $timestamps = false;
    protected $fillable = [
        'student_id',
        'round_id',
        'video_id',
     ];
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
    public function round()
    {
        return $this->belongsTo(Rounds::class, 'round_id');
    }

    // protected static function newFactory(): StudentViewFactory
    // {
    //     // return StudentViewFactory::new();
    // }
}
