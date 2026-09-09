<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\QuestionAnswersModelFactory;

class QuestionAnswersModel extends Model
{
    use HasFactory;

    protected $table = 'question_answers';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'question_id',
        'answer_text',
        'correct_option_id'
    ];
    public function question()
    {
        return $this->belongsTo(QuestionsModel::class, 'question_id');
    }
    public function option()
    {
        return $this->belongsTo(QuestionOptionsModel::class, 'correct_option_id');
    }
    // protected static function newFactory(): QuestionAnswersModelFactory
    // {
    //     // return QuestionAnswersModelFactory::new();
    // }
}
