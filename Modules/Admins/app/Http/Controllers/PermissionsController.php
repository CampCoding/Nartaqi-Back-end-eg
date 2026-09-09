<?php

namespace Modules\Admins\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Admins\Models\Permission;
use Modules\Admins\Http\Requests\PermissionRequest;
use App\Http\Controllers\Controller;

class PermissionsController extends Controller
{
    public function index()
    {
        $permissions = Permission::all();
        return res_data($permissions, 'تم استرجاع الصلاحيات بنجاح', 200);
    }

    public function show(Request $request)
    {
        $permission = Permission::find($request->input('id'));
        if (!$permission) {
            return res_data('الصلاحية غير موجودة', 'failed', 404);
        }
        return res_data($permission, 'تم استرجاع الصلاحية بنجاح', 200);
    }

    public function store(PermissionRequest $request)
    {
        $data = $request->validated();
        $permission = Permission::create($data);
        return res_data($permission, 'تم إضافة الصلاحية بنجاح', 201);
    }

    public function update(PermissionRequest $request)
    {
        $permission = Permission::find($request->input('id'));
        if (!$permission) {
            return res_data('الصلاحية غير موجودة', 'failed', 404);
        }
        $data = $request->validated();
        $permission->update($data);
        return res_data($permission, 'تم تحديث الصلاحية بنجاح', 200);
    }

    public function destroy(Request $request)
    {
        $permission = Permission::find($request->input('id'));
        if (!$permission) {
            return res_data('الصلاحية غير موجودة', 'failed', 404);
        }
        $permission->delete();
        return res_data(null, 'تم حذف الصلاحية بنجاح', 200);
    }
}
