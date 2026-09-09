<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\ExamlabelsModelFactory;

class ExamlabelsModel extends Model
{
    use HasFactory;

    /** 
     * The attributes that are mass assignable.
     */
    public $timestamps = false;
    protected $table = 'exam_labels';
    protected $fillable = [
        'label',
    ];


    public function exams()
    {
        return $this->hasMany(AdminExamModel::class, 'exam_label_id');
    }

    // protected static function newFactory(): ExamlabelsModelFactory
    // {
    //     // return ExamlabelsModelFactory::new();
    // }
}
