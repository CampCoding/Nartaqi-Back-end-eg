<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Modules\Courses\Database\Factories\AdminQuestionsModelFactory;

class AdminQuestionsModel extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'questions';


    protected $fillable = [
        'exam_section_id',
        'question_text',
        'question_type',
        'instructions',
        'is_active',
        'paragraph_id',
        'sort_order',
        'deleted_by',
        'created_at',
        'updated_at',
        'description'
    ];

    protected static function booted(): void
    {
        // New questions go to the end of their section; without this they'd get the column
        // default 0 and jump to the top of a section the admin has already reordered.
        static::creating(function (self $question) {
            if ($question->sort_order === null && $question->exam_section_id) {
                $question->sort_order = (int) static::where('exam_section_id', $question->exam_section_id)->max('sort_order') + 1;
            }
        });
    }

    public function exam_section()
    {
        return $this->belongsTo(AdminExamSectionModel::class, 'exam_section_id');
    }

    public function paragraph()
    {
        return $this->belongsTo(QuestionParagraphsModel::class, 'paragraph_id');
    }
    public function options()
    {
        return $this->hasMany(QuestionOptionsModel::class, 'question_id');
    }

    public function answer()
    {
        return $this->hasOne(QuestionAnswersModel::class, 'question_id');
    }


    // protected static function newFactory(): AdminQuestionsModelFactory
    // {
    //     // return AdminQuestionsModelFactory::new();
    // }
}
