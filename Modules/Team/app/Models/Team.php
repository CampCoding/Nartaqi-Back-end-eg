<?php

namespace Modules\Team\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Team\Database\Factories\TeamFactory;

class Team extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'category',
        'name',
        'role',
        'image',
        'email',
        'hidden',
    ];

    /**
     * Get the team member's image URL.
     */
    protected function image(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function ($value) {
                if (!$value) {
                    return null;
                }

                // If it contains the new domain, extract the path to normalize it
                if (str_contains($value, 'nartaqi.net')) {
                    $parts = explode('storage/', $value);
                    $path = count($parts) > 1 ? $parts[1] : $value;
                    return asset('storage/' . ltrim($path, '/'));
                }

                // If it's already a full URL (but not the old one), return it as is
                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    return $value;
                }

                // Otherwise, treat as a relative path and use the storage asset helper
                return asset('storage/' . ltrim($value, '/'));
            },
        );
    }
}
