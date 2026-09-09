<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\TeachersModelFactory;

class TeachersModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'gender',
        'image',
        'description',
        'facebook',
        'twitter',
        'instagram',
        'linkedin',
        'youtube',
        'tiktok',
        'website',
    ];
    public $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return url('storage/' . $this->image);
    }

    protected $table = 'teachers';
    public function rounds()
    {
        return $this->hasMany(Rounds::class, 'teacher_id');
    }

    // protected static function newFactory(): TeachersModelFactory
    // {
    //     // return TeachersModelFactory::new();
    // }
}
