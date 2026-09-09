<?php

namespace Modules\Cart\Models;

use Modules\Courses\Models\Rounds;
use Illuminate\Database\Eloquent\Model;
use Modules\Authentication\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Store\Models\Store;

// use Modules\Cart\Database\Factories\CartFactory;

class Cart extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'student_id',
        'item_id',
        'quantity',
        'type',
    ];

    // protected static function newFactory(): CartFactory
    // {
    //     // return CartFactory::new();
    // }
    public function student()
    {
        return $this->belongsTo( Student::class);
    }

    public function round()
    {
        return $this->belongsTo(Rounds::class, 'item_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'item_id');
    }

}
