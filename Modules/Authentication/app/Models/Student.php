<?php

namespace Modules\Authentication\Models;

// use Modules\Authentication\Database\Factories\StudentFactory;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Student extends Authenticatable implements JWTSubject

{
    use HasFactory;


    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'token',
        'gender',
        'image',

    ];
    protected $hidden = [
        'password'
    ];


    // protected static function newFactory(): StudentFactory
    // {
    //     // return StudentFactory::new();
    // }
    protected $casts = [
        'image' => 'string',
    ];
    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        return asset('storage/' . $this->image);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
