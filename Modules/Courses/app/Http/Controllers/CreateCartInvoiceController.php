<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Cart\Models\Cart;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Throwable;

class CreateCartInvoiceController extends Controller
{
    /**
     * Create a Fawaterak invoice for the student's entire cart.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $student = $request->user();
        if (!$student) {
            return res_data('Student not found', 'failed', 404);
        }

        $currency = $request->input('currency') ?: 'SAR';

        // Fetch all cart items
        $cartItems = Cart::where('student_id', $student->id)->get();

        if ($cartItems->isEmpty()) {
            return res_data('Cart is empty', 'failed', 400);
        }

        $FAWATERAK_API_URL = 'https://app.fawaterk.com/api/v2';
        $API_KEY = "3fa3a46b49869715bf5a149e126468adb8dfbdc560decdd41e";

        try {
            $fawaterakItems = [];
            $calculatedTotal = 0;

            foreach ($cartItems as $item) {
                if ($item->type === 'rounds') {
                    $item->load('round');
                    $name = $item->round->name ?? 'Course';
                    $price = $item->round->price ?? 0;
                } else {
                    $item->load('store');
                    $name = $item->store->title ?? 'Product';
                    $price = $item->store->price ?? 0;
                }

                $fawaterakItems[] = [
                    'name' => $name,
                    'price' => strval($price),
                    'quantity' => strval($item->quantity)
                ];
                
                $calculatedTotal += (floatval($price) * intval($item->quantity));
            }

            // Split student name
            $fullName = trim($student->name ?: 'Customer');
            $nameParts = preg_split('/\s+/', $fullName);
            $firstName = $nameParts[0];
            $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '.';

            // Secure Internal Discount Calculation
            $discount = 0;
            $couponCode = $request->input('copounData.code');
            if ($couponCode) {
                $couponCode = strtoupper($couponCode);
            }

            if ($couponCode) {
                $coupon = \Modules\Courses\Models\Coupon::where('code', strtoupper($couponCode))
                    ->where('is_active', true)
                    ->where('target', 'store')
                    ->first();

                if ($coupon && $coupon->isValidFor('store')) {
                    // Never let a discount exceed the cart total (e.g. a fixed-value
                    // coupon bigger than the order), which would otherwise produce a
                    // negative invoice total.
                    $discount = min($coupon->getDiscountAmount($calculatedTotal), $calculatedTotal);
                    Log::info("[PayCart] Applied internal discount for coupon $couponCode: $discount");
                } else {
                    Log::warning("[PayCart] Invalid or expired coupon used: $couponCode");
                }
            }

            $finalTotal = $calculatedTotal - floatval($discount);
            
            // Add discount as a negative item if applicable for Fawaterak invoice transparency
            if ($discount > 0) {
                $fawaterakItems[] = [
                    'name' => 'خصم كود: ' . strtoupper($couponCode),
                    'price' => '-' . strval($discount),
                    'quantity' => '1'
                ];
            }

            $frontendUrl = env('FRONTEND_URL', 'https://nartaqi-user.vercel.app');
            $frontendRedirectUrl = rtrim($frontendUrl, '/');
            
            $backendUrl = url('/');
            $platform = $request->input('platform', 'web');
            $successUrl = rtrim($backendUrl, '/') . '/api/payment/success?platform=' . urlencode($platform) . '&frontend_redirect=' . urlencode($frontendRedirectUrl);

            $payload = [
                'cartItems' => $fawaterakItems,
                'cartTotal' => $finalTotal,
                'customer' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $student->phone ?: 'n/a',
                ],
                'currency' => $currency,
                'payLoad' => [
                    'type' => 'cart',
                    'student_id' => $student->id,
                    'orderId' => 'CART-' . time() . '-' . $student->id,
                    'coupon_code' => $couponCode,
                    'currency' => $currency
                ],
                'sendEmail' => true,
                'sendSMS' => false
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $API_KEY,
                'Content-Type' => 'application/json'
            ])->post($FAWATERAK_API_URL . '/createInvoiceLink', $payload);

            if ($response->failed()) {
                throw new Exception('Fawaterak API Error: ' . $response->body());
            }

            $result = $response->json();

            if (isset($result['status']) && $result['status'] === 'success') {
                // Record the pending transaction
                try {
                    \Modules\Courses\Models\PaymentTransaction::create([
                        'invoice_id' => $result['data']['invoice_id'] ?? null,
                        'student_id' => $student->id,
                        'payment_method' => 'Fawaterak',
                        'amount' => $finalTotal,
                        'currency' => $currency,
                        'status' => 'pending',
                        'type' => 'cart',
                        'round_id' => null,
                        'pay_load' => $payload['payLoad'] ?? null,
                        'raw_response' => $result
                    ]);
                } catch (Throwable $dbEx) {
                    Log::error('Failed to log payment transaction in DB: ' . $dbEx->getMessage());
                }

                return res_data([
                    'url' => $result['data']['url'] ?? null,
                    'invoice_id' => $result['data']['invoice_id'] ?? null
                ], 'success', 200);
            }

            return res_data($result['message'] ?? 'Failed to create invoice link', 'failed', 400);

        } catch (Throwable $e) {
            Log::error('Cart Invoice Error: ' . $e->getMessage(), [
                'student_id' => $student->id
            ]);
            return res_data($e->getMessage(), 'failed', 500);
        }
    }
}
