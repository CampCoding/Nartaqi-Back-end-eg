<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\QuestionBankSkillsModelFactory;

class QuestionBankSkillsModel extends Model
{
    use HasFactory;

    protected $table = 'question_bank_skills';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['question_bank_branch_id', 'name'];

    public function branch()
    {
        return $this->belongsTo(QuestionBankBranchesModel::class, 'question_bank_branch_id');
    }

    // protected static function newFactory(): QuestionBankSkillsModelFactory
    // {
    //     // return QuestionBankSkillsModelFactory::new();
    // }
}
