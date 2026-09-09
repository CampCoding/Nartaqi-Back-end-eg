<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\PlacementTestStudentScoresModelFactory;

class PlacementTestStudentScoresModel extends Model
{
    use HasFactory;

    protected $table = 'placement_test_scores';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'student_id',
        'placement_test_id',
        'score',
    ];
}
