<?php

namespace Modules\Admins\Database\Seeders;

use Illuminate\Database\Seeder;

use Modules\Admins\Models\Permission;
use Modules\Admins\Models\Role;

class AdminsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'إدارة المستخدمين', 'description' => 'القدرة على إدارة المستخدمين'],
            ['name' => 'عرض المستخدمين', 'description' => 'القدرة على عرض قائمة المستخدمين'],
            ['name' => 'إضافة مستخدم', 'description' => 'القدرة على إضافة مستخدم جديد'],
            ['name' => 'تعديل مستخدم', 'description' => 'القدرة على تعديل بيانات المستخدم'],
            ['name' => 'حذف مستخدم', 'description' => 'القدرة على حذف المستخدم'],
            ['name' => 'إدارة الأدوار', 'description' => 'القدرة على إدارة الأدوار'],
            ['name' => 'إداره أوراق المستخدم', 'description' => 'إداره أوراق المستخدم'],
        ];

        $permissionsIds = [];
        foreach ($permissions as $p) {
            $permission = Permission::updateOrCreate(
                ['name' => $p['name']],
                ['description' => $p['description']]
            );
            $permissionsIds[] = $permission->id;
        }

        Role::updateOrCreate(
            ['name' => 'مدير النظام'],
            [
                'description' => 'صلاحيات كاملة للنظام',
                'permissions_ids' => $permissionsIds
            ]
        );
    }
}
