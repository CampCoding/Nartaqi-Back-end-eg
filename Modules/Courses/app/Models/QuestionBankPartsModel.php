<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\QuestionBankPartsModelFactory;

class QuestionBankPartsModel extends Model
{
    use HasFactory;

    protected $table = 'question_bank_parts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name'];

    // protected static function newFactory(): QuestionBankPartsModelFactory
    // {
    //     // return QuestionBankPartsModelFactory::new();
    // }
}
