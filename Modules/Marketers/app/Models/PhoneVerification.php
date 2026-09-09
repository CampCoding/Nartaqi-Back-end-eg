<?php

namespace Modules\Marketers\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Marketers\Database\Factories\PhoneVerificationFactory;

class PhoneVerification extends Model
{
    use HasFactory;

    protected $table = 'marketer_phone_verification';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'phone',
        'code',
        'expires_at',
        'verified_at',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at instanceof Carbon && $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return (bool) $this->verified_at;
    }

    // protected static function newFactory(): PhoneVerificationFactory
    // {
    //     // return PhoneVerificationFactory::new();
    // }
}
