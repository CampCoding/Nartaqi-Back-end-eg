<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\CompetitionQuestionsModelFactory;

class CompetitionQuestionsModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'competition_question';

    /**
     * The attributes that are mass assignable.
     */

    protected $fillable = [
        'competition_id',
        'show_date',
        'question_text',
        'question_type'
    ];

    public function competition()
    {
        return $this->belongsTo(CompetitionsModel::class, 'competition_id');
    }

    public function options()
    {
        return $this->hasMany(CompetitionQuestionsOptionsModel::class, 'question_id');
    }

    // protected static function newFactory(): CompetitionQuestionsModelFactory
    // {
    //     // return CompetitionQuestionsModelFactory::new();
    // }
}
