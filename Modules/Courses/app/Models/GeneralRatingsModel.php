<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\GeneralRatingsModelFactory;

class GeneralRatingsModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'general_ratings';

    protected $fillable = [
        "question",
    ];
    public $timestamps = false;
    public function generalAnswersRatings()
    {
        return $this->hasMany(GeneralAnswersRatingsModel::class, 'general_rating_id');
    }

    // protected static function newFactory(): GeneralRatingsModelFactory
    // {
    //     // return GeneralRatingsModelFactory::new();
    // }
}
