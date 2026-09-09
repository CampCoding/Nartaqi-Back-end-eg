<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentFolderExamQuestionModel extends Model
{
    use HasFactory;

    protected $table = 'student_folder_exam_questions';
    public $timestamps = false;

    protected $fillable = [
        'folder_exam_id',
        'question_id',
        'sort_order',
    ];

    public function folderExam()
    {
        return $this->belongsTo(StudentFolderExamModel::class, 'folder_exam_id');
    }

    public function question()
    {
        return $this->belongsTo(AdminQuestionsModel::class, 'question_id');
    }
}
