<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\PlacementTestQuestionsModelFactory;

class PlacementTestQuestionsModel extends Model
{
    use HasFactory;

    protected $table = 'placement_test_questions';

    protected $fillable = [
        'placement_test_section_id',
        'question_text',
        'question_type',
        'paragraph_id',
        'label',
        'instructions'
    ];

    public function section()
    {
        return $this->belongsTo(PlacementTestSectionsModel::class, 'placement_test_section_id');
    }

    public function paragraph()
    {
        return $this->belongsTo(PlacementTestParagraphsModel::class, 'paragraph_id');
    }

    public function options()
    {
        return $this->hasMany(PlacementTestQuestionOptionsModel::class, 'question_id');
    }
}
