<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\AdminQuestionsModelFactory;

class AdminQuestionsModel extends Model
{
    use HasFactory;

    public $table = 'questions';


    protected $fillable = [
        'exam_section_id',
        'question_text',
        'question_type',
        'instructions',
        'is_active',
        'paragraph_id',
        'created_at',
        'updated_at',
        'description'
    ];
    public function exam_section()
    {
        return $this->belongsTo(AdminExamSectionModel::class, 'exam_section_id');
    }

    public function paragraph()
    {
        return $this->belongsTo(QuestionParagraphsModel::class, 'paragraph_id');
    }
    public function options()
    {
        return $this->hasMany(QuestionOptionsModel::class, 'question_id');
    }

    public function answer()
    {
        return $this->hasOne(QuestionAnswersModel::class, 'question_id');
    }


    // protected static function newFactory(): AdminQuestionsModelFactory
    // {
    //     // return AdminQuestionsModelFactory::new();
    // }
}
