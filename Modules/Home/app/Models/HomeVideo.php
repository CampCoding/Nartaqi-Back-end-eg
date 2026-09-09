<?php

namespace Modules\Home\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HomeVideo extends Model
{
    use HasFactory;

    protected $table = 'home_videos';

    protected $fillable = [
        'video_url',
    ];
}
