<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// use Modules\Courses\Database\Factories\LessonsModelFactory;

class LessonsModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    public $timestamps = false;
    const CREATED_AT = null;
    const UPDATED_AT = null;
    protected $fillable = [
        'round_content_id',
        'title',
        'description',
        'type',
        'show_date',

    ];
    protected $table = 'lessons';
    public function round_content()
    {
        return $this->belongsTo(RoundContetModel::class, 'round_content_id');
    }

    public function videos(){
        return $this->hasMany(VideosModel::class, 'lesson_id');

    }



    // protected static function newFactory(): LessonsModelFactory
    // {
    //     // return LessonsModelFactory::new();
    // }
}
