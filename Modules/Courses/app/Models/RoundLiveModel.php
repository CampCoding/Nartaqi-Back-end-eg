<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoundLiveModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    // public $timestamps = false;
    protected $table = 'round_lives';
    protected $fillable = [
        'round_content_id',
        'lesson_id',
        'title',
        'link',
        'time',
        'date',
        'active',
        'finished',
        'password',
        'meeting_id',
        'end_time'

    ];
    public function round_content()
    {
        return $this->belongsTo(RoundContetModel::class, 'round_content_id');
    }
}
