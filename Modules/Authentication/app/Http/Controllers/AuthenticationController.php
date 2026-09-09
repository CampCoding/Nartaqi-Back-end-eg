<?php

namespace Modules\Authentication\Http\Controllers;

use Illuminate\Contracts\Validation\Validator;
// use Carbon\Carbon;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Authentication\Http\Requests\ChangePasswordRequest;
use Modules\Authentication\Http\Requests\SendCodeRequest;
use Modules\Authentication\Http\Requests\VerifyCodeRequest;
use Modules\Authentication\Http\Requests\StudentSignUp;
use Modules\Authentication\Http\Requests\ResetPasswordRequest;
use Modules\Authentication\Http\Requests\LoginRequest;
use Modules\Authentication\Http\Requests\StudentInfoRequest;
use Modules\Authentication\Models\PhoneVerification;
use Modules\Authentication\Models\PhonePasswordReset;
use Modules\Authentication\Models\Student;


class AuthenticationController extends Controller
{

    private function formatMinutesRemaining($expiresAt, $referenceTime = null): string
    {
        $expiresAt = $expiresAt instanceof Carbon ? $expiresAt : Carbon::parse($expiresAt);
        $referenceTime = $referenceTime ?: Carbon::now();

        $minutesRemaining = ceil($referenceTime->diffInMinutes($expiresAt, false));

        if ($minutesRemaining <= 0) {
            return 'منتهي الصلاحية';
        }

        return $minutesRemaining . ($minutesRemaining > 2 ? ' دقائق' : ' دقيقة');
    }

    /**
     * Display a listing of the resource.
     */


    public function signUp(StudentSignUp $request)
    {
        $user = $request->validated();
        // Require verified phone before creating account
        $verification = PhoneVerification::where('phone', $user['phone'] ?? null)->first();
        if (!$verification || !$verification->verified_at) {
            return res_data('يرجى تأكيد رقم الهاتف أولاً', 'error', 422);
        }
        $user['password'] = bcrypt($request['password']);
        $check_insert = Student::create($user);
        if ($check_insert) {
            return res_data('تم تسجيل البياتات بنجاح برجاء تسجيل الدخول', 'success', 201);
        }
        return res_data('Student not created', 'error', 500);
    }

    public function sendCode(SendCodeRequest $request)
    {
        $phone = $request->validated('phone');
        $userNowInput = $request->validated('expires_at'); // الوقت الحالي من جهاز المستخدم

        // تحويل الوقت المرسل من المستخدم لكائن Carbon
        $userNow = Carbon::parse($userNowInput);

        // التأكد من عدم وجود مستخدم بنفس الرقم
        $student = Student::where('phone', $phone)->first();
        if ($student) {
            return res_data('رقم الهاتف مسجل', 'error', 404);
        }

        // توليد كود عشوائي 6 أرقام
        $code = (string) random_int(100000, 999999);

        // إعداد وقت انتهاء الكود بعد 10 دقائق من وقت المستخدم
        $expiresAt = $userNow->copy()->addMinutes(10);

        // حفظ أو تحديث سجل التحقق
        $verification = PhoneVerification::updateOrCreate(
            ['phone' => $phone],
            [
                'code' => $code,
                'expires_at' => $expiresAt,
                'verified_at' => null,
                'attempts' => 0
            ]
        );

        // تحميل ملف SMS
        $smsFile = base_path('Modules/Authentication/smsfile.php');
        if (file_exists($smsFile)) {
            require_once $smsFile;
        }

        if (!function_exists('sendWawpMessage')) {
            return res_data('SMS sender not available', 'error', 500);
        }

        // إرسال الرسالة للمستخدم
        $result = sendWawpMessage(
            $phone,
            "رمز تأكيد منصة نرتقي هو: {$code} تنتهي صلاحيته بعد: " . $this->formatMinutesRemaining($expiresAt, $userNow)
        );
        // $result = json_decode('رمز تاكيد منصه نرتقي هو : {1231} تنتهي صلاحيه الكود بعد  : {10 دقائق}', true);

        if (isset($result['error'])) {
            return res_data($result['error'], 'error', 400);
        }

        return res_data('تم إرسال رمز التأكيد', 'success', 200);
    }

    // original verify code
    // public function verifyCode(VerifyCodeRequest $request)
    // {
    //     $phone = $request->validated('phone');
    //     $code  = $request->validated('code');
    //     $userNowInput = $request->validated('verified_at'); // الوقت الحالي من جهاز المستخدم

    //     // تحويل الوقت المستلم من المستخدم لكائن Carbon
    //     $userNow = Carbon::parse($userNowInput);

    //     // جلب سجل التحقق
    //     $verification = PhoneVerification::where('phone', $phone)->first();
    //     if (!$verification) {
    //         return res_data('لم يتم طلب رمز لهذا الرقم', 'error', 402);
    //     }

    //     // التحقق من انتهاء الصلاحية بالنسبة لوقت المستخدم
    //     if ($verification->expires_at && $userNow->greaterThan(Carbon::parse($verification->expires_at))) {
    //         return res_data('انتهت صلاحية الرمز، اطلب رمزاً جديداً', 'error', 422);
    //     }

    //     // زيادة عدد المحاولات
    //     $verification->attempts = ($verification->attempts ?? 0) + 1;

    //     // التحقق من صحة الرمز
    //     if (hash_equals($verification->code, $code)) {
    //         $verification->verified_at = $userNow; // حفظ وقت التحقق وفق وقت المستخدم
    //         $verification->save();
    //         return res_data('تم التحقق من رقم الهاتف', 'success', 200);
    //     }

    //     $verification->save();
    //     return res_data('رمز غير صحيح', 'error', 422);
    // }

    public function verifyCode(VerifyCodeRequest $request)
    {
        $phone = $request->validated('phone');
        $code  = $request->validated('code');
        $userNowInput = $request->validated('verified_at'); // الوقت الحالي من جهاز المستخدم

        // تحويل الوقت المستلم من المستخدم لكائن Carbon
        $userNow = Carbon::parse($userNowInput);

        // جلب سجل التحقق
        $verification = PhoneVerification::where('phone', $phone)->first();

        $verification->verified_at = $userNow; // حفظ وقت التحقق وفق وقت المستخدم
        $verification->save();
        return res_data('تم التحقق من رقم الهاتف', 'success', 200);
    }



    // Forgot password: send code
    public function forgotSendCode(SendCodeRequest $request)
    {
        $phone = $request->validated('phone');
        $userNowInput = $request->validated('expires_at'); // الوقت المحلي من المستخدم

        // تحويل الوقت المستلم من المستخدم لكائن Carbon
        $userNow = Carbon::parse($userNowInput);

        // التأكد من وجود المستخدم
        $student = Student::where('phone', $phone)->first();
        if (!$student) {
            return res_data('الحساب غير موجود', 'error', 402);
        }

        // توليد كود عشوائي
        $code = (string) random_int(100000, 999999);

        // إعداد وقت انتهاء الكود بعد 10 دقائق من وقت المستخدم
        $expiresAt = $userNow->copy()->addMinutes(10);

        // حفظ أو تحديث سجل إعادة تعيين كلمة المرور
        PhonePasswordReset::updateOrCreate(
            ['phone' => $phone],
            [
                'code' => $code,
                'expires_at' => $expiresAt,
                'verified_at' => null,
                'attempts' => 0
            ]
        );

        // التأكد من وجود ملف الـ SMS
        $smsFile = base_path('Modules/Authentication/smsfile.php');
        if (file_exists($smsFile)) {
            require_once $smsFile;
        }
        if (!function_exists('sendWawpMessage')) {
            return res_data('SMS sender not available', 'error', 500);
        }

        // إرسال الرسالة مع حساب الدقائق المتبقية بالنسبة للمستخدم
        $result = sendWawpMessage(
            $phone,
            "رمز استعادة كلمة المرور: {$code} ينتهي بعد: " . $this->formatMinutesRemaining($expiresAt, $userNow)
        );

        if (isset($result['error'])) {
            return res_data($result['error'], 'error', 400);
        }

        return res_data('تم إرسال الرمز', 'success', 200);
    }


    // Forgot password: verify code
    public function forgotVerifyCode(VerifyCodeRequest $request)
    {
        $phone = $request->validated('phone');
        $code  = $request->validated('code');
        $verified_at  = $request->validated('verified_at');

        $record = PhonePasswordReset::where('phone', $phone)->first();
        if (!$record) {
            return res_data('لم يتم طلب رمز لهذا الرقم', 'error', 402);
        }

        $verified_at = Carbon::parse($verified_at);

        if ($verified_at->greaterThan($record->expires_at)) {
            return res_data('انتهت صلاحية الرمز', 'error', 422);
        }


        $record->attempts = ($record->attempts ?? 0) + 1;
        if (hash_equals($record->code, $code)) {
            $record->verified_at = $verified_at;
            $record->save();
            return res_data('تم التحقق من الرمز', 'success', 200);
        }
        $record->save();
        return res_data('رمز غير صحيح', 'error', 422);
    }

    // Forgot password: reset
    public function resetPassword(ResetPasswordRequest $request)
    {
        $phone = $request->validated('phone');
        $password = $request->validated('password');

        $record = PhonePasswordReset::where('phone', $phone)->first();

        if (!$record || !$record->verified_at) {
            return res_data('يرجى التحقق من الرمز أولاً', 'error', 422);
        }


        $student = Student::where('phone', $phone)->first();
        if (!$student) {
            return res_data('الحساب غير موجود', 'error', 402);
        }

        $student->password = bcrypt($password);
        $student->save();

        // Invalidate the reset record
        $record->delete();

        return res_data('تم تحديث كلمة المرور', 'success', 200);
    }

    public function index()
    {
        return view('authentication::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('authentication::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('authentication::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('authentication::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}

    public function login(LoginRequest $request)
    {
        $phone = $request->validated('phone');
        $password = $request->validated('password');

        $student = Student::where('phone', $phone)->first();
        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'بيانات الدخول غير صحيحة'], 401);
        }

        if (!\Illuminate\Support\Facades\Hash::check($password, $student->password)) {
            // return response()->jsdata: on(['status' => 'error', 'message' => ''], 401);
            return  res_data('بيانات الدخول غير صحيحة', 'error', 401);
        }

        $expiry = now()->addMonth()->timestamp;
        $tokenData = json_encode(['token' => bin2hex(random_bytes(32)), 'exp' => $expiry]);
        $student->token = base64_encode($tokenData);
        $student->save();

        return  res_data($student, 'success', 200);

        // Fallback: plain response without token
        // return response()->json(['status' => 'success', 'message' => $stu]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $student = $request->user();
        if (!$student) {
            return res_data('المستخدم غير مصرح له. يرجى تسجيل الدخول', 401);
        }

        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $student->password)) {
            return res_data('كلمة المرور القديمة غير صحيحة', 'error', 422);
        }

        $student->password = bcrypt($request->new_password);
        $student->save();

        return res_data('تم تغيير كلمة المرور بنجاح', 'success', 200);
    }

    public function studentInfo(Request $request)
    {
        $student = $request->user();

        if (!$student) {
            return res_data('المستخدم غير موجود', 'error', 404);
        }
        return res_data($student, 'success', 200);
    }

    public function updateStudentInfo(StudentInfoRequest $request)
    {
        $student = $request->user();

        if (!$student) {
            return res_data('المستخدم غير موجود', 'error', 404);
        }

        $validated = $request->validated();

        if (request()->hasFile('image')) {
            $destinationPath = public_path('storage/' . 'students');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $image = request()->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $imageName);
            $validated['image'] = 'students' . '/' . $imageName;
        }
        $student->update($validated);

        return res_data('تم تحديث الملف الشخصي بنجاح', 'success', 200);
    }
}
