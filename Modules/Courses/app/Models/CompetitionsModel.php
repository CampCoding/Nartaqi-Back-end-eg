<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\CompetitionsModelFactory;

class CompetitionsModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'competitions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'competition_name',
        'type',
        'idea',
        'prize',
        'start_date',
        'end_date',
        'active',
        'image',
        "question_type"
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'active' => 'boolean'
    ];

    protected $appends = ['image_url'];

    /**
     * Get the full URL for the image.
     */
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    /**
     * Prepare a date for array / JSON serialization.
     * This ensures dates are returned in local timezone without 'Z' suffix.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function questions()
    {
        return $this->hasMany(CompetitionQuestionsModel::class, 'competition_id');
    }

    // protected static function newFactory(): CompetitionsModelFactory
    // {
    //     // return CompetitionsModelFactory::new();
    // }
}
