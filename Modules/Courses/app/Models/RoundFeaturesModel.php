<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\RoundFeaturesModelFactory;

class RoundFeaturesModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'round_features';
    public $timestamps = false;
    protected $fillable = [
        'round_id',
        'title',
        'description',
        'image',

    ];
    public function round()
    {
        return $this->belongsTo(Rounds::class, 'round_id');
    }

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        return asset('storage/' . $this->image);
    }


    // protected static function newFactory(): RoundFeaturesModelFactory
    // {
    //     // return RoundFeaturesModelFactory::new();
    // }
}
