<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\PlacementTestQuestionOptionsModelFactory;

class PlacementTestQuestionOptionsModel extends Model
{
    use HasFactory;

    protected $table = 'placement_test_question_options';

    protected $fillable = [
        'question_id',
        'option_text',
        'is_correct',
    ];

    public function question()
    {
        return $this->belongsTo(PlacementTestQuestionsModel::class, 'question_id');
    }
}
