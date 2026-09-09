<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Authentication\Models\Student;

class StudentMockExamScratchModel extends Model
{
    use HasFactory;

    protected $table = 'student_mock_exam_scratches';

    protected $fillable = [
        'student_id',
        'exam_id',
        'question_id',
        'scratch_data',
    ];

    protected $casts = [
        'scratch_data' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function exam()
    {
        return $this->belongsTo(AdminExamModel::class, 'exam_id');
    }

    public function question()
    {
        return $this->belongsTo(AdminQuestionsModel::class, 'question_id');
    }
}
