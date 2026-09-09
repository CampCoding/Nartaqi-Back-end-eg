<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\ExamModelFactory;

class ExamModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'exams';
    protected $fillable = [
        'title',
        'description',
        'exam_type',
        'round_id',
        'round_content_id',
        'lesson_id',
        'free',
        'time',
        'date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    public function round()
    {
        return $this->belongsTo(Rounds::class, 'round_id');
    }
    public function round_content()
    {
        return $this->belongsTo(RoundContetModel::class, 'round_content_id');
    }
    public function lesson()
    {
        return $this->belongsTo(LessonsModel::class, 'lesson_id');
    }

    // protected static function newFactory(): ExamModelFactory
    // {
    //     // return ExamModelFactory::new();
    // }
}
