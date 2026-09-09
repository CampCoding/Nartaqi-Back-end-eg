<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\QuestionsBankModelFactory;

class QuestionsBankModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'questions_bank';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'question_text',
        'question_type',
        'question_bank_skills_id',
        'type',  // ('mcq', 'paragraph' )
        'paragraph_id', // null if type  = mcq
        'instructions'
    ];

    /**
     * Get the options for the question.
     */
    public function options()
    {
        return $this->hasMany(QuestionsBankOptionsModel::class, 'question_id', 'id');
    }

    public function paragraph()
    {
        return $this->belongsTo(QuestionBankParagraphModel::class, 'paragraph_id', 'id');
    }

    // protected static function newFactory(): QuestionsBankModelFactory
    // {
    //     // return QuestionsBankModelFactory::new();
    // }
}
