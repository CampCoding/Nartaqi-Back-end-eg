<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\CompetitionQuestionsOptionsModelFactory;

class CompetitionQuestionsOptionsModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'competition_question_options';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        "question_id",
        "option_text",
        "is_correct"
    ];

    public function question()
    {
        return $this->belongsTo(CompetitionQuestionsModel::class, 'question_id');
    }

    // protected static function newFactory(): CompetitionQuestionsOptionsModelFactory
    // {
    //     // return CompetitionQuestionsOptionsModelFactory::new();
    // }
}
