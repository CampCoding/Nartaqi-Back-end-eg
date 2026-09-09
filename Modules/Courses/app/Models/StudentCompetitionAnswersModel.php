<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Authentication\Models\Student;

// use Modules\Courses\Database\Factories\StudentCompetitionAnswersModelFactory;

class StudentCompetitionAnswersModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    // SELECT `id`, `question_id`, `competition_id`, `answer_text`, `correct_or_not`, `created_at`, `updated_at` FROM `competition_question_answers` WHERE 1
    protected $fillable = [
        'question_id',
        'competition_id',
        'answer_text',
        'correct_or_not',
        'student_id',
    ];

    protected $table = 'competition_question_answers';

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    // protected static function newFactory(): StudentCompetitionAnswersModelFactory
    // {
    //     // return StudentCompetitionAnswersModelFactory::new();
    // }
}
