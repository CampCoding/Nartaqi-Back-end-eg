<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentFolderExamAnswerModel extends Model
{
    use HasFactory;

    protected $table = 'student_folder_exam_answers';

    protected $fillable = [
        'folder_exam_id',
        'question_id',
        'selected_option_id',
        'student_answer',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function folderExam()
    {
        return $this->belongsTo(StudentFolderExamModel::class, 'folder_exam_id');
    }

    public function question()
    {
        return $this->belongsTo(AdminQuestionsModel::class, 'question_id');
    }

    public function selectedOption()
    {
        return $this->belongsTo(QuestionOptionsModel::class, 'selected_option_id');
    }
}
