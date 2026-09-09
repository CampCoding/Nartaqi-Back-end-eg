<?php

namespace Modules\Marketers\Http\Controllers;

use App\Mail\MarketerMail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\Marketers\Models\Marketer;
use Modules\Marketers\Models\MarketerCode;
use Modules\Marketers\Http\Requests\MarketerRequest;
use Modules\Marketers\Http\Requests\MarketerCodeRequest;

class AdminMarketerController extends Controller
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

    public function getMarketers()
    {
        $marketers = Marketer::with("code")->get();
        return res_data($marketers, "success", 200);
    }

    public function generateCode(MarketerCodeRequest $codeRequest)
    {
        $data = $codeRequest->validated();

        $validatedStatus = $codeRequest->validate([
            'status' => 'required|in:approved,rejected,pending',
        ]);

        $marketer = Marketer::find($data['marketer_id']);

        if (!$marketer) {
            return res_data("هذا المسوق غير موجود", "failed", 404);
        }

        $marketer->status = $validatedStatus['status'];
        $marketer->save();

        if ($marketer->status === 'approved') {

            $existingCode = MarketerCode::where('marketer_id', $marketer->id)->first();

            if ($existingCode) {
                return res_data("هذا المسوق لديه كود بالفعل", "failed", 400);
            }

            $plainPassword = Str::random(10);
            $marketer->password = Hash::make($plainPassword);
            $marketer->save();


            $discount = $data['discount_percentage'];

            $prefix = strtoupper(substr(preg_replace('/\s+/', '', $marketer->name), 0, 2));
            $discountPart = str_pad($discount, 2, '0', STR_PAD_LEFT);
            $randomPart = strtoupper(substr(bin2hex(random_bytes(1)), 0, 2));
            $code = "{$prefix}{$discountPart}{$randomPart}";

            while (MarketerCode::where('code', $code)->exists()) {
                $randomPart = strtoupper(substr(bin2hex(random_bytes(1)), 0, 2));
                $code = "{$prefix}{$discountPart}{$randomPart}";
            }

            $data['code'] = $code;

            $marketerCode = MarketerCode::create($data);

            Mail::to($marketer->email)->send(new MarketerMail($plainPassword, $code));

            return res_data([
                'marketer' => $marketer,
                'code_info' => $marketerCode,
                'message' => 'تمت الموافقة وإصدار الكود بنجاح'
            ], 'success', 200);
            
        }elseif ($marketer->status === 'suspended') {

            return res_data('تم إيقاف المسوق', 'success', 200);
        }

        return res_data('تم تعيين حالة المسوق إلى قيد المراجعة', 'success', 200);
    }


}
