<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\RoundContetModelFactory;

class RoundContetModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'round_contents'; // or 'round_contents' – use your actual table name
    public $timestamps = false;
    protected $fillable = [
        'round_id',
        'title',
        'description',
        'type',
        'show_date',
        'sort_number'

    ];
    // protected $casts = [
    //     'type' => 'string',
    // ];

    public function round()
    {
        return $this->belongsTo(Rounds::class, 'round_id');
    }

    public function videos()
    {
        return $this->hasMany(VideosModel::class, 'round_content_id');
    }

    public function round_lives()
    {
        return $this->hasMany(RoundsLiveModel::class, 'round_content_id');
    }
    public function lessons()
    {
        return $this->hasMany(LessonsModel::class, 'round_content_id');
    }
    // protected static function newFactory(): RoundContetModelFactory
    // {
    //     // return RoundContetModelFactory::new();
    // }
}
