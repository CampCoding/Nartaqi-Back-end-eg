<?php

namespace Modules\Favourite\Models;

use Modules\Courses\Models\Rounds;
use Illuminate\Database\Eloquent\Model;
use Modules\Authentication\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Favourite\Database\Factories\FavouriteFactory;

class Favourite extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'favourites';
    protected $fillable = [
        'student_id',
        'round_id',
    ];

    // protected static function newFactory(): FavouriteFactory
    // {
    //     // return FavouriteFactory::new();
    // }
        public function student()
    {
        return $this->belongsTo( Student::class, 'student_id');
    }

    public function round()
    {
        return $this->belongsTo(Rounds::class);
    }
}
