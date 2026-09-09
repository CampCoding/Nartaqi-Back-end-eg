<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\Courses\Models\Rounds;
use Modules\Authentication\Models\Student;

class PaymentController extends Controller
{
    /**
     * عرض وسائل الدفع المتاحة للعميل
     */
    public function showMethods($round_id, $student_id)
    {
        $apiKey = env('FAWATERK_API_KEY');
        $baseUrl = rtrim(env('FAWATERK_URL', 'https://app.fawaterk.com'), '/');

        // جلب بيانات الكورس للتأكد من وجوده
        $round = Rounds::findOrFail($round_id);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->get($baseUrl . '/getPaymentmethods');

        $data = $response->json();

        if (isset($data['status']) && $data['status'] == 'success') {
            return view('payment.methods', [
                'methods' => $data['data'],
                'round_id' => $round_id,
                'student_id' => $student_id,
                'round' => $round
            ]);
        }

        return response()->json([
            'error' => 'فشل في جلب وسائل الدفع',
            'api_response' => $data,
        ]);
    }

    /**
     * الوظيفة اللي بتبعت البيانات لفواتيرك وتبدأ عملية الدفع
     */
    public function initiatePayment(Request $request, $round_id, $student_id)
    {
        $apiKey = env('FAWATERK_API_KEY');
        $baseUrl = rtrim(env('FAWATERK_URL', 'https://app.fawaterk.com'), '/');
        $methodId = $request->query('method_id', 3);

        // جلب بيانات الطالب والكورس الحقيقية
        $round = Rounds::findOrFail($round_id);
        $student = Student::findOrFail($student_id);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post($baseUrl . '/invoiceInitPay', [
            'payment_method_id' => $methodId,
            'cartTotal' => $round->price, // السعر الحقيقي للكورس
            'currency' => 'EGP',
            'customer' => [
                'first_name' => $student->name,
                'last_name' => ' ', // فواتيرك يطلب الاسم الأول والأخير
                'email' => $student->email ?? ($student->phone . '@nartaqi.com'),
                'phone' => $student->phone,
            ],
            'redirectionUrls' => [
                'successUrl' => route('payment.success'),
                'failUrl' => route('payment.fail'),
                'pendingUrl' => route('payment.success'),
            ],
            'redirectOption' => true,
            'cartItems' => [
                [
                    'name' => $round->name,
                    'price' => $round->price,
                    'quantity' => 1
                ]
            ],
            'payLoad' => json_encode([ // إرسال بيانات إضافية لنستقبلها في الـ Webhook لاحقاً
                'round_id' => $round_id,
                'student_id' => $student_id
            ])
        ]);

        $data = $response->json();

        if (isset($data['status']) && $data['status'] == 'success') {
            $paymentData = $data['data']['payment_data'] ?? [];

            // 1. إذا كان رابط تحويل (فيزا أو غيره)
            if (isset($paymentData['redirectTo'])) {
                return redirect($paymentData['redirectTo']);
            }

            // 3. إذا كان أمان أو بساطة أو محفظة إلكترونية (أكواد دفع)
            $code = $paymentData['fawryCode'] ?? ($paymentData['amanCode'] ?? ($paymentData['masaryCode'] ?? ($paymentData['meezaReference'] ?? null)));

            if ($code) {
                return view('payment.fawry', [
                    'fawryCode' => $code,
                    'expireDate' => $paymentData['expireDate'] ?? 'يرجى الدفع في أقرب وقت',
                    'qrCode' => $paymentData['meezaQrCode'] ?? null,
                    'method' => isset($paymentData['meezaReference']) ? 'Wallet' : 'Code'
                ]);
            }

            // 4. إذا كان هناك رابط قديم (للاحتياط)
            $url = $data['data']['url'] ?? ($paymentData['url'] ?? null);
            if ($url) {
                return redirect($url);
            }
        }

        return "عذراً، حدث خطأ في عملية الدفع: " . ($data['message'] ?? 'يرجى مراجعة بيانات الدفع');
    }

    /**
     * عرض صفحة النجاح
     */
    public function paymentSuccess()
    {
        return view('payment.success');
    }

    /**
     * عرض صفحة الفشل
     */
    public function paymentFail()
    {
        return view('payment.failed');
    }
}
