<?php

namespace Modules\Admins\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'permissions_ids',
    ];

    protected $casts = [
        'permissions_ids' => 'array',
    ];
}
