<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\RoundResourceLinksFactory;

class RoundResourceLinks extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    // INSERT INTO `resource_links`(`resource_link_id`, `round_id`, `telegram_link`, `whatsapp_link`) VALUES ('[value-1]','[value-2]','[value-3]','[value-4]')
    protected $table = 'resource_links';
    public $timestamps = false;
    protected $fillable = ['round_id', 'telegram_link', 'whatsapp_link'];

    // protected static function newFactory(): RoundResourceLinksFactory
    // {
    //     // return RoundResourceLinksFactory::new();
    // }
}
