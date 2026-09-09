<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\QuestionBankParagraphModelFactory;

class QuestionBankParagraphModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'question_bank_paragraphs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'question_bank_skills_id',
        'paragraph_content',
    ];

    /**
     * Get the questions for this paragraph.
     */
    public function questions()
    {
        return $this->hasMany(QuestionsBankModel::class, 'paragraph_id', 'id');
    }
}
