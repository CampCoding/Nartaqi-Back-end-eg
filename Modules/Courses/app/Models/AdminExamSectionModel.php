<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminExamSectionModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'exam_sections';
    protected $fillable = [
        'exam_id',
        'title',
        'description',
        'time_if_free',
        'created_at',
        'updated_at',
        'success_percentage'
    ];

    public function exam()
    {
        return $this->belongsTo(AdminExamModel::class, 'exam_id');
    }
    public function questions()
    {
        return $this->hasMany(QuestionsModel::class, 'exam_section_id');
    }
    public function answers()
        {
        return $this->hasMany(QuestionAnswersModel::class, 'exam_section_id');
    }
    public function paragraphs()
    {
        return $this->hasMany(QuestionParagraphsModel::class, 'exam_section_id');
    }
    public function question_options()
    {
        return $this->hasMany(QuestionOptionsModel::class, 'exam_section_id');
    }
}
