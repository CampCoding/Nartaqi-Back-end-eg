<?php

namespace Modules\Admins\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Admins\Models\Role;
use Modules\Admins\Http\Requests\RoleRequest;
use App\Http\Controllers\Controller;

class RolesController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        return res_data($roles, 'تم استرجاع الأدوار بنجاح', 200);
    }

    public function show(Request $request)
    {
        $role = Role::find($request->input('id'));
        if (!$role) {
            return res_data('الدور غير موجود', 'failed', 404);
        }
        return res_data($role, 'تم استرجاع الدور بنجاح', 200);
    }

    public function store(RoleRequest $request)
    {
        $data = $request->validated();
        $role = Role::create($data);
        return res_data($role, 'تم إضافة الدور بنجاح', 201);
    }

    public function update(RoleRequest $request)
    {
        $role = Role::find($request->input('id'));
        if (!$role) {
            return res_data('الدور غير موجود', 'failed', 404);
        }
        $data = $request->validated();
        $role->update($data);
        return res_data($role, 'تم تحديث الدور بنجاح', 200);
    }

    public function destroy(Request $request)
    {
        $role = Role::find($request->input('id'));
        if (!$role) {
            return res_data('الدور غير موجود', 'failed', 404);
        }
        $role->delete();
        return res_data(null, 'تم حذف الدور بنجاح', 200);
    }
}
