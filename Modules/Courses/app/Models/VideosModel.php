<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\VideosModelFactory;

class VideosModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    public $timestamps = false;
    protected $table = 'videos';
    protected $fillable = [
        'lesson_id',
        'title',
        'description',
        'time',
        'free',
        'vimeo_link',
        'youtube_link',
    ];
    // public function rounds()
    // {
    //     return $this->hasMany(Rounds::class, 'teacher_id');
    // }
    public function lessons()
    {
        return $this->hasMany(LessonsModel::class, 'lesson_id');
    }

    // protected static function newFactory(): VideosModelFactory
    // {
    //     // return VideosModelFactory::new();
    // }
}
