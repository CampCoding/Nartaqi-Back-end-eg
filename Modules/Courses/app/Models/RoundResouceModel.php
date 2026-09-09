<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\RoundResouceModelFactory;

class RoundResouceModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'round_resources';
    public $timestamps = false;

    protected $fillable = ['round_id', 'title', 'description', 'url', 'show_date' ];
    public function round()
    {
        return $this->belongsTo(Rounds::class, 'round_id');
    }

    // protected static function newFactory(): RoundResouceModelFactory
    // {
    //     // return RoundResouceModelFactory::new();
    // }
}
