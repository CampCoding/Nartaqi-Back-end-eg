<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\StudentinquiryModelFactory;

class StudentinquiryModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    public $timestamps = false;
    protected $table = 'student_inquiry';
    protected $fillable = [
        'name',
        'message_type',
        'content',
        'phone',
        'solved'
    ];

    // protected static function newFactory(): StudentinquiryModelFactory
    // {
    //     // return StudentinquiryModelFactory::new();
    // }
}
