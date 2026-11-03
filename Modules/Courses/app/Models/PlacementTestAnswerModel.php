<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;

class PlacementTestAnswerModel extends Model
{
    protected $table = 'placement_test_answers';

    protected $fillable = [
        'score_id',
        'question_id',
        'option_id',
        'answer_text',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function attempt()
    {
        return $this->belongsTo(PlacementTestStudentScoresModel::class, 'score_id');
    }

    public function question()
    {
        return $this->belongsTo(PlacementTestQuestionsModel::class, 'question_id');
    }
}
