<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Courses\Http\Requests\CreateInvoiceRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Throwable;

class CreateInvoiceController extends Controller
{
    /**
     * Create a Fawaterak invoice for a single course (round).
     *
     * @param CreateInvoiceRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(CreateInvoiceRequest $request)
    {
        $orderData = $request->validated();
        $userData = $orderData['userData'] ?? [];
        $currency = !empty($orderData['currency']) ? $orderData['currency'] : 'SAR';
        
        $FAWATERAK_API_URL = 'https://app.fawaterk.com/api/v2';
        $API_KEY = "3fa3a46b49869715bf5a149e126468adb8dfbdc560decdd41e";

        try {
            $cartItems = [];
            $originalTotal = 0;

            foreach ($orderData['items'] as $item) {
                $cartItems[] = [
                    'name' => $item['productData']['name'] ?? 'Course',
                    'price' => strval($item['priceAfterDiscount'] ?? 0),
                    'quantity' => strval($item['quantity'] ?? 1)
                ];
                $originalTotal += (floatval($item['priceAfterDiscount'] ?? 0) * intval($item['quantity'] ?? 1));
            }

            // Secure Internal Discount Calculation
            $discount = 0;
            $couponCode = $orderData['copounData']['code'] ?? null;
            if ($couponCode) {
                $couponCode = strtoupper($couponCode);
            }

            if ($couponCode) {
                $couponQuery = \Modules\Courses\Models\Coupon::where('code', strtoupper($couponCode))
                    ->where('is_active', true)
                    ->where('target', 'rounds');

                $coupon = (clone $couponQuery)->where('round_id', $orderData['round_id'])->first()
                    ?? (clone $couponQuery)->whereNull('round_id')->first();

                if ($coupon && $coupon->isValidFor('rounds', $orderData['round_id'])) {
                    // Do not perform discount subtraction since it is already applied on the frontend.
                    // Keep the couponCode so it is recorded as used upon successful payment.
                    Log::info("[Invoice] Valid coupon $couponCode found. Skipping internal discount calculation because the frontend handles it.");
                } else {
                    // If the coupon is invalid/expired, clear the code so it is not registered.
                    Log::warning("[Invoice] Coupon $couponCode is invalid or expired.");
                    $couponCode = null;
                }
            }

            $finalTotal = $originalTotal;
            
            // Add discount as a negative item if applicable (set to 0 / skipped since discount is already applied on frontend)
            if ($discount > 0) {
                $cartItems[] = [
                    'name' => 'خصم كود: ' . strtoupper($couponCode),
                    'price' => '-' . strval($discount),
                    'quantity' => '1'
                ];
            }

            $fullName = trim($userData['full_name'] ?? '');
            $nameParts = preg_split('/\s+/', $fullName);
            $firstName = $nameParts[0] ?? 'Customer';
            $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '.';

            $orderId = 'ORD-' . time() . '-' . ($orderData['student_id'] ?? '0');

            $frontendUrl = env('FRONTEND_URL', 'https://nartaqi-user.vercel.app');
            $frontendRedirectUrl = rtrim($frontendUrl, '/') . '/course/' . $orderData['round_id'];
            
            $backendUrl = url('/');
            $platform = $request->input('platform', 'web');
            $successUrl = rtrim($backendUrl, '/') . '/api/payment/success?platform=' . urlencode($platform) . '&frontend_redirect=' . urlencode($frontendRedirectUrl);

            $payload = [
                'cartItems' => $cartItems,
                'cartTotal' => $finalTotal,
                'customer' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $userData['phone'] ?? '',
                    'address' => 'N/A'
                ],
                'currency' => $currency,
                'payLoad' => [
                    'orderId' => $orderId,
                    'student_id' => $orderData['student_id'],
                    'round_id' => $orderData['round_id'],
                    'coupon_code' => $couponCode,
                    'type' => 'single',
                    'currency' => $currency
                ],
                'regionPrice' => $orderData['regionPrice'] ?? 0,
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
                        'student_id' => $orderData['student_id'] ?? null,
                        'payment_method' => 'Fawaterak',
                        'amount' => $finalTotal,
                        'currency' => $currency,
                        'status' => 'pending',
                        'type' => 'single',
                        'round_id' => $orderData['round_id'] ?? null,
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
            Log::error('Invoice Creation Error: ' . $e->getMessage(), [
                'student_id' => $orderData['student_id'] ?? null
            ]);
            return res_data($e->getMessage(), 'failed', 500);
        }
    }
}
