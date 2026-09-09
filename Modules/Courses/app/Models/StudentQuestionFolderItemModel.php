<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentQuestionFolderItemModel extends Model
{
    use HasFactory;

    protected $table = 'student_question_folder_items';
    public $timestamps = false;

    protected $fillable = [
        'folder_id',
        'question_id',
    ];

    public function folder()
    {
        return $this->belongsTo(StudentQuestionFolderModel::class, 'folder_id');
    }

    public function question()
    {
        return $this->belongsTo(AdminQuestionsModel::class, 'question_id');
    }
}
