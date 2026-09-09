<?php

namespace Modules\Blogs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Authentication\Models\Student;
// use Modules\Blogs\Database\Factories\BlogCommentFactory;

class BlogComment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'blog_id',
        'student_id',
        'comment',
        'rating',
        'hidden',
    ];

    // protected static function newFactory(): BlogCommentFactory
    // {
    //     // return BlogCommentFactory::new();
    // }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
    public function blog()
    {
        return $this->belongsTo(Blog::class, 'blog_id');
    }


}
