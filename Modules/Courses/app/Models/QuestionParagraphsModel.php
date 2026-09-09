<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\QuestionParagraphsModelFactory;

class QuestionParagraphsModel extends Model
{
    use HasFactory;

    protected $table = 'question_paragraphs';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'exam_section_id',
        'paragraph_content',
        'voice',
        'description'
    ];
    public function question()
    {
        return $this->belongsTo(QuestionsModel::class, 'question_id');
    }
    public function options()
    {
        return $this->hasMany(QuestionOptionsModel::class, 'question_id');
    }
    // protected static function newFactory(): QuestionParagraphsModelFactory
    // {
    //     // return QuestionParagraphsModelFactory::new();
    // }
}
