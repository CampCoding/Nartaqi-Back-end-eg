<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\AdminExamModelFactory;

class AdminExamModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'exams';
    protected $fillable = [
        'title',
        'description',
        'free',
        'time',
        'date',
        'level',
        'created_at',
        'updated_at',
        'deleted_at',
        'type',
        'success_percentage',
        'exam_label_id',
        'from_copy',
        'copied_from_id'
    ];


    // protected static function newFactory(): AdminExamModelFactory
    // {
    //     // return AdminExamModelFactory::new();
    // }

    public function exam_sections()
    {
        return $this->hasMany(AdminExamSectionModel::class, 'exam_id');
    }

    /**
     * Whether exams.copied_from_id exists yet (the migration may run after the code is uploaded).
     */
    public static function tracksCopySource(): bool
    {
        static $has = null;

        return $has ??= \Illuminate\Support\Facades\Schema::hasColumn('exams', 'copied_from_id');
    }

    /**
     * Ids of exams that are this bank exam itself or copies made from it.
     */
    public static function familyIds(int $bankExamId): array
    {
        $ids = [$bankExamId];
        if (static::tracksCopySource()) {
            $ids = array_merge($ids, static::where('copied_from_id', $bankExamId)->pluck('id')->all());
        }

        return $ids;
    }

    public function questions()
    {
        return $this->hasManyThrough(
            AdminQuestionsModel::class,
            AdminExamSectionModel::class,
            'exam_id', // Foreign key on exam_sections table
            'exam_section_id', // Foreign key on questions table
            'id', // Local key on exams table
            'id' // Local key on exam_sections table
        );
    }

    /**
     * Get the exam label for this exam
     */
    public function examLabel()
    {
        return $this->belongsTo(ExamlabelsModel::class, 'exam_label_id');
    }
}
