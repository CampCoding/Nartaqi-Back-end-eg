<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\GeneralAnswersRatingsModelFactory;

class GeneralAnswersRatingsModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'general_answers_ratings';

    public $timestamps = false;
    protected $fillable = [
        "general_rating_id",
        "answer",
    ];

    public function generalRatings()
    {
        return $this->belongsTo(GeneralRatingsModel::class, 'general_rating_id');
    }
    // protected static function newFactory(): GeneralAnswersRatingsModelFactory
    // {
    //     // return GeneralAnswersRatingsModelFactory::new();
    // }
}
