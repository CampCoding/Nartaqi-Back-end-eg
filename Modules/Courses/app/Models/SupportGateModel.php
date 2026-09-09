<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\SupportGateModelFactory;

class SupportGateModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    public $timestamps = false;
    protected $table = 'support';
    protected $fillable = [
        'title',
        'youtube_link',
        'description',
    ];

    // protected static function newFactory(): SupportGateModelFactory
    // {
    //     // return SupportGateModelFactory::new();
    // }
}
