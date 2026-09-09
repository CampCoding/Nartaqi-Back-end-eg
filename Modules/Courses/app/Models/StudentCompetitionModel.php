<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Authentication\Models\Student;

// use Modules\Courses\Database\Factories\StudentCompetitionModelFactory;

class StudentCompetitionModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    // SELECT `id`, `student_id`, `competition_id`, `date_created` FROM `student_competitions` WHERE 1
    protected $fillable = [
        'student_id',
        'competition_id',
        'date_created'
    ];

    protected $table = 'student_competitions';
    public $timestamps = false;

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function competition()
    {
        return $this->belongsTo(CompetitionsModel::class);
    }
    // protected static function newFactory(): StudentCompetitionModelFactory
    // {
    //     // return StudentCompetitionModelFactory::new();
    // }
}
