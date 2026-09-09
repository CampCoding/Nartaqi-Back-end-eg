<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\QuestionOptionsModelFactory;

class QuestionOptionsModel extends Model
{
    use HasFactory;

    protected $table = 'question_options';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'question_id',
        'option_text',
        'is_correct',
        'question_explanation'
    ];
    public function question()
    {
        return $this->belongsTo(QuestionsModel::class, 'question_id');
    }
    public function answer()
    {
        return $this->hasOne(QuestionAnswersModel::class, 'correct_option_id');
    }

    // protected static function newFactory(): QuestionOptionsModelFactory
    // {
    //     // return QuestionOptionsModelFactory::new();
    // }
}
