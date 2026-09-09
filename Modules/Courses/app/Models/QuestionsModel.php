<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\QuestionsModelFactory;


class QuestionsModel extends Model
{
    use HasFactory;

    protected $table = 'questions';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'exam_section_id',
        'question_text',
        'question_type',
        'instructions',
        'is_active',
        "paragraph_id"
    ];

    public function options()
    {
        return $this->hasMany(QuestionOptionsModel::class, 'question_id');
    }

    public function answer()
    {
        return $this->hasOne(QuestionAnswersModel::class, 'question_id');
    }

    public function paragraph()
    {
        return $this->belongsTo(QuestionParagraphsModel::class, 'paragraph_id');
    }

    // protected static function newFactory(): QuestionsModelFactory
    // {
    //     // return QuestionsModelFactory::new();
    // }
}
