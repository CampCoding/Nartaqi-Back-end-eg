<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\PlacementTestParagraphsModelFactory;

class PlacementTestParagraphsModel extends Model
{
    use HasFactory;

    protected $table = 'placement_test_paragraphs';

    protected $fillable = [
        'placement_test_section_id',
        'paragraph_content',
    ];

    public function section()
    {
        return $this->belongsTo(PlacementTestSectionsModel::class, 'placement_test_section_id');
    }

    public function questions()
    {
        return $this->hasMany(PlacementTestQuestionsModel::class, 'paragraph_id');
    }
}
