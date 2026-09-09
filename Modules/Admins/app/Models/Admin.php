<?php

namespace Modules\Admins\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Admins\Database\Factories\AdminFactory;

class Admin extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'token',
        'role_id',
    ];

    protected $appends = ['permissions'];

    public function roleRelation()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function getPermissionsAttribute()
    {
        $role = $this->roleRelation;
        if (!$role) {
            return [];
        }

        $permissionsIds = $role->permissions_ids ?: [];
        $permissions = Permission::whereIn('id', $permissionsIds)->get();

        return $permissions->map(function ($p) use ($role) {
            return [
                'name' => $p->name,
                'description' => $p->description,
                'role_id' => $role->id,
                'permission_id' => (string)$p->id,
            ];
        })->toArray();
    }

    // protected static function newFactory(): AdminFactory
    // {
    //     // return AdminFactory::new();
    // }
}
