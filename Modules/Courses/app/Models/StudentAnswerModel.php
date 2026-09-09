<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Authentication\Models\Student;

// use Modules\Courses\Database\Factories\StudentAnswerModelFactory;

class StudentAnswerModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'student_answers_data';
    protected $fillable = [
        'student_id',
        'exam_id',
        'question_id',
        'type',
        'student_answer',
        'correct_answer',
        'is_correct',
    ];
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
    public function exam()
    {
        return $this->belongsTo(ExamModel::class, 'exam_id');
    }
    public function question()
    {
        return $this->belongsTo(QuestionsModel::class, 'question_id');
    }
}
