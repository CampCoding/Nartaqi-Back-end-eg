<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Authentication\Models\Student;

// use Modules\Courses\Database\Factories\StudentsRateModelFactory;

class StudentsRateModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'student_rates';
    protected $fillable = [
        'round_id',
        'student_id',
        'rate',
        'comment',
        'hidden',
        'recommend_to_friends',
        'moderator_interaction',
        'response_speed',
        'platform_ease_of_use',
        'notifications_rating',
    ];
    public function round()
    {
        return $this->belongsTo(Rounds::class, 'round_id');
    }
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    // protected static function newFactory(): StudentsRateModelFactory
    // {
    //     // return StudentsRateModelFactory::new();
    // }
}
