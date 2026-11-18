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
        'sort_number',
        'round_exam_section_id'
    ];

    public function round_exam_section()
    {
        return $this->belongsTo(RoundExamSectionModel::class, 'round_exam_section_id');
    }
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

    /**
     * One assignment per exam. The same exam can end up assigned twice to the same round/lesson
     * (e.g. a double click on "add"); every listing shows it once, using the row returned by
     * keeperFor(). The original order of the collection is kept.
     */
    public static function uniqueByExam($assignments)
    {
        $keepIds = $assignments->groupBy('exam_id')
            ->map(fn ($group) => static::keeperFor($group)->id)
            ->flip();

        return $assignments->filter(fn ($a) => isset($keepIds[$a->id]))->values();
    }

    /**
     * Which of several assignments of the same exam to keep: the one the admin set a show_date on
     * (explicit data), otherwise the last one by (sort_number, id), which is the one the admin's
     * getAllExamsByRoundId has always shown.
     */
    public static function keeperFor($group)
    {
        $ordered = $group->sortBy([['sort_number', 'asc'], ['id', 'asc']])->values();
        $withShowDate = $ordered->filter(fn ($a) => filled($a->show_date));

        return ($withShowDate->isNotEmpty() ? $withShowDate : $ordered)->last();
    }
}
