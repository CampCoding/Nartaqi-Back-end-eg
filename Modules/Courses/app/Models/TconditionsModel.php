<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\TconditionsModelFactory;

class TconditionsModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    public $timestamps = false;
    protected $table = 'term_conditions';
    protected $fillable = [
        'content',
        'type',
    ];

    // protected static function newFactory(): TconditionsModelFactory
    // {
    //     // return TconditionsModelFactory::new();
    // }
}
