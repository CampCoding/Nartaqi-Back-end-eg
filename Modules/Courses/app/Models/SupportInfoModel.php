<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\SupportInfoModelFactory;

class SupportInfoModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'support_info';

    protected $fillable = [
        'whatsapp_number',
        'whatsapp_message',
        'show_whatsapp',
        'phone_number',
        'support_email',
        'show_email',
        'working_hours_text',
        'response_time_text',
        'active',
        'location'
    ];

    // Only use updated_at timestamp
    const CREATED_AT = null;

    // protected static function newFactory(): SupportInfoModelFactory
    // {
    //     // return SupportInfoModelFactory::new();
    // }
}
