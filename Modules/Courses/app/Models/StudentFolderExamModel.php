<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Authentication\Models\Student;

class StudentFolderExamModel extends Model
{
    use HasFactory;

    protected $table = 'student_folder_exams';

    protected $fillable = [
        'student_id',
        'folder_id',
        'title',
        'mode',
        'question_count',
        'status',
        'score',
        'percentage',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function folder()
    {
        return $this->belongsTo(StudentQuestionFolderModel::class, 'folder_id');
    }

    public function questions()
    {
        return $this->hasMany(StudentFolderExamQuestionModel::class, 'folder_exam_id')->orderBy('sort_order');
    }

    public function answers()
    {
        return $this->hasMany(StudentFolderExamAnswerModel::class, 'folder_exam_id');
    }
}
