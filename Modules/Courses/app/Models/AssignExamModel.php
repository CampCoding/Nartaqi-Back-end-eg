<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\AssignExamModelFactory;

class AssignExamModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'assign_exam_round';
    protected $fillable = [
        'id',
        'type',
        'exam_id',
        'lesson_or_round_id',
        'show_date',
        'sort_number'
    ];
    public function exam()
    {
        return $this->belongsTo(AdminExamModel::class, 'exam_id');
    }

    public function lesson_or_round()
    {
        return $this->belongsTo(Rounds::class, 'lesson_or_round_id');
    }
    public function lesson()
    {
        return $this->belongsTo(LessonsModel::class, 'lesson_or_round_id');
    }
}
