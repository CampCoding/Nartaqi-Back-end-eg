<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\SocialAccountsModelFactory;

class SocialAccountsModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'social_accounts_platform';
    public $timestamps = false;

    protected $fillable = [
        'platform_name',
        'platform_link',
        'image',
    ];


    public $appends = ['image_url'];
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }
        return asset('storage/' . $this->image);
    }
    // protected static function newFactory(): SocialAccountsModelFactory
    // {
    //     // return SocialAccountsModelFactory::new();
    // }
}
