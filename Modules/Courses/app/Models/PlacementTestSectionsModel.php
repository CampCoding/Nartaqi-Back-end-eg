<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\PlacementTestSectionsModelFactory;

class PlacementTestSectionsModel extends Model
{
    use HasFactory;

    protected $table = 'placement_test_sections';

    protected $fillable = [
        'placement_test_id',
        'time_if_free',
        'title',
        'description',
    ];

    public function placementTest()
    {
        return $this->belongsTo(PlacementTestModel::class, 'placement_test_id');
    }

    public function questions()
    {
        return $this->hasMany(PlacementTestQuestionsModel::class, 'placement_test_section_id');
    }

    public function paragraphs()
    {
        return $this->hasMany(PlacementTestParagraphsModel::class, 'placement_test_section_id');
    }

    // This counts questions that belong to paragraphs in this section
    public function paragraphQuestions()
    {
        return $this->hasManyThrough(
            PlacementTestQuestionsModel::class,
            PlacementTestParagraphsModel::class,
            'placement_test_section_id',
            'paragraph_id'
        );
    }
}
