<?php

namespace Modules\Marketers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Marketers\Database\Factories\MarketerCodeFactory;

class MarketerCode extends Model
{
    use HasFactory;

    protected $table = 'marketers_codes';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'marketer_id',
        'code',
        'discount_percentage',
        'commission_percentage',
    ];

    public function marketer()
    {
        return $this->belongsTo(Marketer::class);
    }

    // protected static function newFactory(): MarketerCodeFactory
    // {
    //     // return MarketerCodeFactory::new();
    // }
}
