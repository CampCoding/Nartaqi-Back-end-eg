<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Authentication\Models\Student;

class StudentQuestionFolderModel extends Model
{
    use HasFactory;

    protected $table = 'student_question_folders';

    protected $fillable = [
        'student_id',
        'name',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function items()
    {
        return $this->hasMany(StudentQuestionFolderItemModel::class, 'folder_id');
    }

    public function exams()
    {
        return $this->hasMany(StudentFolderExamModel::class, 'folder_id');
    }
}
