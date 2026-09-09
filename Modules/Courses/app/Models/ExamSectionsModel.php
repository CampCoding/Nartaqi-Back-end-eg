<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\ExamSectionsModelFactory;

class ExamSectionsModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'exam_sections';
    protected $fillable = [
        'exam_id',
        'title',
        'description',
        'time_if_free' ,
        'level'

    ];
    public function exam()
    {
        return $this->belongsTo(ExamModel::class, 'exam_id');
    }
    public function questions()
    {
        return $this->hasMany(QuestionsModel::class, 'exam_section_id');
    }
    // public function essay()
    // {
    //     return $this->hasManyThrough(QuestionAnswersModel::class, QuestionsModel::class, 'exam_section_id', 'question_id');
    // }
    public function paragraphs()
    {
        return $this->hasMany(QuestionParagraphsModel::class, 'exam_section_id');
    }
    public function options()
    {
        return $this->hasManyThrough(QuestionOptionsModel::class, QuestionsModel::class, 'exam_section_id', 'question_id');
    }

    // protected static function newFactory(): ExamSectionsModelFactory
    // {
    //     // return ExamSectionsModelFactory::new();
    // }
}
