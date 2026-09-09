<?php

namespace Modules\Marketers\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Modules\Marketers\Models\Marketer;
use Modules\Marketers\Models\PhoneVerification;
use Modules\Marketers\Http\Requests\MarketerRequest;
use Modules\Marketers\Http\Requests\SendCodeRequest;
use Modules\Marketers\Http\Requests\VerifyCodeRequest;

class MarketersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('marketers::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('marketers::create');
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
        return view('marketers::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('marketers::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}

    public function applyMarketer(MarketerRequest $request){

        $marketer = $request->validated();

        $marketer['password'] = bcrypt($request['password']);
        if( $request->hasFile('cv') ) {
            $path = $request->file('cv')->store('marketers_cvs', 'public');
            $url = rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;
            $marketer['cv'] = str_replace('camp-coding.site', 'nartaqi.net', $url);

        }
        Marketer::create($marketer);

        return res_data($marketer, "success", 201);
    }

    public function getProfile(Request $request){

        $marketer = $request->marketer;
        if(!$marketer){
            return res_data("لا يوجد مسوق بهذا المعرف", 404);
        }

        if($marketer->status != 'approved'){
            return res_data("حساب المسوق الخاص بك قيد المراجعة من قبل الإدارة", 403);
        }

        return res_data($marketer, "success", 200);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'whatsapp_number' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $marketer = Marketer::where('whatsapp_number', $data['whatsapp_number'])->first();

        if (!$marketer || !Hash::check($data['password'], $marketer->password)) {
            return res_data("البريد الإلكتروني أو كلمة المرور غير صحيحة", 401);
        }


        $expiry = now()->addMonth()->timestamp;
        $tokenData = json_encode(['token' => bin2hex(random_bytes(32)), 'exp' => $expiry]);
        $marketer->token = base64_encode($tokenData);
        $marketer->save();

        if($marketer->status != 'approved'){
            return res_data("حساب المسوق الخاص بك قيد المراجعة من قبل الإدارة", 403);
        }
        return res_data([
            'marketer' => $marketer,
        ], "تم تسجيل الدخول بنجاح", 200);

    }

    public function sendCode(SendCodeRequest $request)
    {
        $phone = $request->validated('phone');

        // Generate 4-digit code and expiry (5 minutes)
        $code = (string) random_int(1000, 9999);
        $expiresAt = now()->addMinutes(10);

        // Upsert verification record
        $verification = PhoneVerification::updateOrCreate(
            ['phone' => $phone],
            ['code' => $code, 'expires_at' => $expiresAt, 'verified_at' => null, 'attempts' => 0]
        );

        // Load SMS sender
        $smsFile = base_path('Modules/Authentication/smsfile.php');
        if (file_exists($smsFile)) {
            require_once $smsFile;
        }

        if (!function_exists('sendWawpMessage')) {
            return res_data('SMS sender not available', 'error', 500);
        }
        // $result = sendWawpMessage($phone, "رمز تأكيد منصه نرتقي هو : {$code} تنتهي صلاحيه الكود بعد  : {$expiresAt}");

        $result = sendWawpMessage(
            $phone,
            "رمز تأكيد منصة نرتقي هو: {$code} تنتهي صلاحيته بعد: " . formatMinutesRemaining($expiresAt)
        );

        if (isset($result['error'])) {
            return res_data($result['error'], 'error', 502);
        }

        return res_data('تم إرسال رمز التأكيد', 'success', 200);
    }

    public function verifyCode(VerifyCodeRequest $request)
    {
        $phone = $request->validated('phone');
        $code  = $request->validated('code');

        $verification = PhoneVerification::where('phone', $phone)->first();
        if (!$verification) {
            return res_data('لم يتم طلب رمز لهذا الرقم', 'error', 402);
        }

        if ($verification->isExpired()) {
            return res_data('انتهت صلاحية الرمز، اطلب رمزاً جديداً', 'error', 422);
        }

        // Increment attempts, simple rate-limiting/lockout could be added here
        $verification->attempts = ($verification->attempts ?? 0) + 1;

        if (hash_equals($verification->code, $code)) {
            $verification->verified_at = now();
            $verification->save();
            return res_data('تم التحقق من رقم الهاتف', 'success', 200);
        }

        $verification->save();
        return res_data('رمز غير صحيح', 'error', 422);
    }


}
