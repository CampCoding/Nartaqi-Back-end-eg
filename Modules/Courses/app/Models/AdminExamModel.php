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
        'from_copy'
    ];


    // protected static function newFactory(): AdminExamModelFactory
    // {
    //     // return AdminExamModelFactory::new();
    // }

    public function exam_sections()
    {
        return $this->hasMany(AdminExamSectionModel::class, 'exam_id');
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
