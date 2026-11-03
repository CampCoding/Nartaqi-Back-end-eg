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

    public function student()
    {
        return $this->belongsTo(\Modules\Authentication\Models\Student::class, 'student_id');
    }

    public function placementTest()
    {
        return $this->belongsTo(PlacementTestModel::class, 'placement_test_id');
    }

    public function answers()
    {
        return $this->hasMany(PlacementTestAnswerModel::class, 'score_id');
    }

    /**
     * The student's mark from a score like "10/20" (the part suggestions are matched against).
     */
    public static function degreeOf(?string $score): int
    {
        $parts = explode('/', (string) $score);

        return (int) trim($parts[0] ?? '0');
    }
}
