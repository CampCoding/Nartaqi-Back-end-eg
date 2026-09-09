<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Authentication\Models\Student;

class StudentCompetitionAnswerModel extends Model
{
    use HasFactory;

    protected $table = 'student_competition_answers';
    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'competition_id',
        'question_id',
        'option_id',
        'is_correct',
        'answered_at'
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'answered_at' => 'datetime'
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function competition()
    {
        return $this->belongsTo(CompetitionsModel::class);
    }

    public function question()
    {
        return $this->belongsTo(CompetitionQuestionsModel::class, 'question_id');
    }

    public function option()
    {
        return $this->belongsTo(CompetitionQuestionsOptionsModel::class, 'option_id');
    }
}
