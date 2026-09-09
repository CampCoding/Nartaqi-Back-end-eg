<?php

namespace Modules\Marketers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Marketers\Database\Factories\MarketerFactory;

class Marketer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'token',
        'national_id',
        'city',
        'description',
        'cv',
        'account_owner',
        'account_number',
        'iban_number',
        'whatsapp_number',
        'status',
    ];

    public function getCvAttribute($value)
    {
        if (!$value) {
            return null;
        }
        return str_replace('camp-coding.site', 'nartaqi.net', $value);
    }

    public function code()
    {
        return $this->hasOne(MarketerCode::class);
    }

    // protected static function newFactory(): MarketerFactory
    // {
    //     // return MarketerFactory::new();
    // }
}
