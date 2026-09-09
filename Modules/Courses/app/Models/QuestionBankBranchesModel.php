<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\QuestionBankBranchesModelFactory;

class QuestionBankBranchesModel extends Model
{
    use HasFactory;

    protected $table = 'question_bank_branches';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['question_bank_parts_id', 'name'];

    public function part()
    {
        return $this->belongsTo(QuestionBankPartsModel::class, 'question_bank_parts_id');
    }

    // protected static function newFactory(): QuestionBankBranchesModelFactory
    // {
    //     // return QuestionBankBranchesModelFactory::new();
    // }
}
