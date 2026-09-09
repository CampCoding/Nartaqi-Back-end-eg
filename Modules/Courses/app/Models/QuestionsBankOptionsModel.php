<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\QuestionsBankOptionsModelFactory;

class QuestionsBankOptionsModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'question_bank_options';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'question_id',
        'option_text',
        'is_correct',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_correct' => 'boolean'
    ];

    /**
     * Get the question that owns the option.
     */
    public function question()
    {
        return $this->belongsTo(QuestionsBankModel::class, 'question_id', 'id');
    }

    // protected static function newFactory(): QuestionsBankOptionsModelFactory
    // {
    //     // return QuestionsBankOptionsModelFactory::new();
    // }
}
