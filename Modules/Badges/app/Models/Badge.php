<?php

namespace Modules\Badges\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Authentication\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Badges\Database\Factories\BadgeFactory;

class Badge extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'image',
        'category',
    ];

    public function students()
    {
    return $this->belongsToMany(Student::class, 'student_badges')
                ->withPivot('round_id')
                ->withTimestamps();
    }
    // protected static function newFactory(): BadgeFactory
    // {
    //     // return BadgeFactory::new();
    // }
}
