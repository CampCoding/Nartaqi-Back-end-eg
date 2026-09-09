<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Authentication\Models\Student;
use Modules\Courses\Models\ExamModel;
// use Modules\Courses\Database\Factories\StudentScoreModelFactory;

class StudentScoreModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'student_scores';
    protected $fillable = [
        'student_id',
        'exam_id',
        'score',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
    public function exam()
    {
        return $this->belongsTo(ExamModel::class, 'exam_id');
    }
    // protected static function newFactory(): StudentScoreModelFactory
    // {
    //     // return StudentScoreModelFactory::new();
    // }
}
