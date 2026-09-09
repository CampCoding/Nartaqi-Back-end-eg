<?php

namespace Modules\Badges\Models;

use Modules\Courses\Models\Rounds;
use Illuminate\Database\Eloquent\Model;
use Modules\Authentication\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\BelongsToRelationship;
// use Modules\Badges\Database\Factories\StudentBadgeFactory;

class StudentBadge extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'student_id',
        'badge_id',
        'round_id',
    ];

    public function badge()
    {
        return $this->belongsTo(Badge::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function round()
    {
        return $this->belongsTo(Rounds::class);
    }

    // protected static function newFactory(): StudentBadgeFactory
    // {
    //     // return StudentBadgeFactory::new();
    // }
}
