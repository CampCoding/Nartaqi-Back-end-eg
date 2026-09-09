<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Store\Database\Factories\StoreFactory;

class Store extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'store';

    protected $fillable = [
        'title',
        'description',
        'price',
        'category',
        'image',
        'hidden',
    ];

    protected $casts = [
        'hidden' => 'boolean',
    ];

    public function images()
    {
        return $this->hasMany(StoreImage::class);
    }

    public function books()
    {
        return $this->hasMany(StoreBook::class);
    }
}
