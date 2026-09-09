<?php

namespace Modules\Admins\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Modules\Admins\Models\Admin;
use Modules\Admins\Http\Requests\AdminRequest;
use Modules\Admins\Http\Requests\AdminLoginRequest;
use App\Http\Controllers\Controller;

class AdminsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $admins = Admin::with('roleRelation')->get();
        return res_data($admins, 'تم استرجاع المشرفين بنجاح', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AdminRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        // Generate token for the new admin since the database requires token to be not null
        $expiry = now()->addMonth()->timestamp;
        $tokenData = json_encode(['token' => bin2hex(random_bytes(32)), 'exp' => $expiry]);
        $data['token'] = base64_encode($tokenData);

        $admin = Admin::create($data);
        $admin->load('roleRelation');
        return res_data($admin, 'تم إضافة المشرف بنجاح', 201);
    }

    /**
     * Show the specified resource.
     */
    public function show(Request $request, $id = null)
    {
        $adminId = $id ?: $request->input('id');
        $admin = Admin::with('roleRelation')->find($adminId);
        if (!$admin) {
            return res_data('المشرف غير موجود', 'failed', 404);
        }
        return res_data($admin, 'تم استرجاع المشرف بنجاح', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AdminRequest $request, $id = null)
    {
        $adminId = $id ?: $request->input('id');
        $admin = Admin::find($adminId);
        if (!$admin) {
            return res_data('المشرف غير موجود', 'failed', 404);
        }

        $data = $request->validated();

        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $admin->update($data);
        $admin->load('roleRelation');
        return res_data($admin, 'تم تحديث المشرف بنجاح', 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id = null)
    {
        $adminId = $id ?: $request->input('id');
        $admin = Admin::find($adminId);
        if (!$admin) {
            return res_data('المشرف غير موجود', 'failed', 404);
        }
        $admin->delete();
        return res_data(null, 'تم حذف المشرف بنجاح', 200);
    }

    /**
     * Update the password of an admin directly.
     */
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:admins,id',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'id.required' => 'معرف المشرف مطلوب.',
            'id.exists' => 'المشرف غير موجود.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'يجب أن تكون كلمة المرور 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $admin = Admin::with('roleRelation')->find($request->input('id'));
        $admin->password = Hash::make($request->input('password'));
        $admin->save();

        return res_data($admin, 'تم تحديث كلمة المرور بنجاح', 200);
    }

    public function login(AdminLoginRequest $request)
    {
        $phone = $request->validated('phone');
        $password = $request->validated('password');

        $admin = Admin::with('roleRelation')->where('phone', $phone)->first();

        if (!$admin) {
            return res_data('بيانات الدخول غير صحيحة', 'error', 401);
        }

        if (!Hash::check($password, $admin->password)) {
            return res_data('بيانات الدخول غير صحيحة', 'error', 401);
        }

        $expiry = now()->addMonth()->timestamp;
        $tokenData = json_encode(['token' => bin2hex(random_bytes(32)), 'exp' => $expiry]);
        $admin->token = base64_encode($tokenData);
        $admin->save();
        $admin->makeHidden(['password']);

        return res_data($admin, 'تم تسجيل الدخول بنجاح', 200);
    }


}
