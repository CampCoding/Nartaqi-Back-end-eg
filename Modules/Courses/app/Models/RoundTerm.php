<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoundTerm extends Model
{
    use HasFactory;

    protected $table = 'round_terms';

    protected $fillable = [
        'round_id',
        'title',
        'points',
    ];

    protected $casts = [
        'points' => 'array',
    ];

    public function round()
    {
        return $this->belongsTo(Rounds::class, 'round_id');
    }
}


