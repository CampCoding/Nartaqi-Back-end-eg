<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\RoundsLiveModelFactory;

class RoundsLiveModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'round_lives';
    protected $fillable = [
        'round_content_id',
        'title',
        'link',
        'time',
        'end_time',
        'date'
    ];
    public function round_content()
    {
        return $this->belongsTo(RoundContetModel::class, 'round_content_id');
    }


    // protected static function newFactory(): RoundsLiveModelFactory
    // {
    //     // return RoundsLiveModelFactory::new();
    // }
}
