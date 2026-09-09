<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\PlacementTestModelFactory;

class PlacementTestModel extends Model
{
    use HasFactory;

    protected $table = 'placement_test';

    protected $fillable = [
        'category_part_id',
        'title',
        'description',
    ];

    public function sections()
    {
        return $this->hasMany(PlacementTestSectionsModel::class, 'placement_test_id');
    }
}
