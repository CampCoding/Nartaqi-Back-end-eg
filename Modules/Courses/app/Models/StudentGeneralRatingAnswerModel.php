<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Authentication\Models\Student;

class StudentGeneralRatingAnswerModel extends Model
{
    use HasFactory;

    protected $table = 'student_general_rating_answers';

    public $timestamps = false;
    protected $fillable = [
        'student_id',
        'general_rating_id',
        'general_answer_rating_id',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function generalRating()
    {
        return $this->belongsTo(GeneralRatingsModel::class, 'general_rating_id');
    }

    public function generalAnswerRating()
    {
        return $this->belongsTo(GeneralAnswersRatingsModel::class, 'general_answer_rating_id');
    }
}
