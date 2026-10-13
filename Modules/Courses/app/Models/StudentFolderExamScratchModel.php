<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;

class StudentFolderExamScratchModel extends Model
{
    protected $table = 'student_folder_exam_scratches';

    protected $fillable = [
        'folder_exam_id',
        'question_id',
        'scratch_data',
    ];

    protected $casts = [
        'scratch_data' => 'array',
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
