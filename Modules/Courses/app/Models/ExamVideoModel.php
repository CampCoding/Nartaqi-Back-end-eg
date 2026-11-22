<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\ExamVideoModelFactory;

class ExamVideoModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'lesson_id',
        'title',
        'description',
        'video_url',
        'for_type',
        'is_locked'
    ];
    protected $table = 'exam_videos';

    protected $casts = [
        'is_locked' => 'boolean',
    ];
    public function lesson()
    {
        return $this->belongsTo(LessonsModel::class, 'lesson_id');
    }


    // protected static function newFactory(): ExamVideoModelFactory
    // {
    //     // return ExamVideoModelFactory::new();
    // }
}
