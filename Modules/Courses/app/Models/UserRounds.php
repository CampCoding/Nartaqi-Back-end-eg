<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Authentication\Models\Student;

// use Modules\Courses\Database\Factories\UserRoundsFactory;

class UserRounds extends Model
{
    use HasFactory;

    protected $table = 'student_rounds';
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'student_id',
        'round_id',
        'payment_id',
        'day',
        'time',
        'status', // active, inactive, completed
        'end_date'
    ];
    public function round()
    {
        return $this->belongsTo(Rounds::class, 'round_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
    // protected static function newFactory(): UserRoundsFactory
    // {
    //     // return UserRoundsFactory::new();
    // }
}
