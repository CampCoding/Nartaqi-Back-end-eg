<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;

class RoundExamSectionModel extends Model
{
    protected $table = 'round_exam_sections';

    protected $fillable = [
        'round_id',
        'title',
        'description',
        'sort_number',
    ];

    public function assignments()
    {
        return $this->hasMany(AssignExamModel::class, 'round_exam_section_id');
    }

    /**
     * Error message when $sectionId can't hold this assignment, or null when it can.
     * Only whole-round exams have sections, and the section must belong to that same round.
     */
    public static function assignmentError($sectionId, string $type, $roundId): ?string
    {
        if ($sectionId === null || $sectionId === '') {
            return null;
        }
        if ($type !== 'full_round') {
            return 'الأقسام متاحة لاختبارات الدورة كاملة فقط (full_round)';
        }
        if (!static::where('id', $sectionId)->where('round_id', $roundId)->exists()) {
            return 'القسم غير موجود في هذه الدورة';
        }

        return null;
    }
}
