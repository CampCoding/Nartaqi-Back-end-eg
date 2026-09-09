<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.myfatoorah.api_key');
        // Ensure base URL doesn't have a trailing slash
        $this->baseUrl = rtrim(config('services.myfatoorah.base_url'), '/');
    }

    /**
     * Create a payment session (InitiateSession)
     * This is used to initialize the embedded payment form
     */
    public function initiateSession(Request $request)
    {
        try {
            $url = "{$this->baseUrl}/v2/InitiateSession";

            // Send empty object {} as body
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($url, (object)[]);

            $data = $response->json();

            if (!isset($data['IsSuccess']) || !$data['IsSuccess']) {
                Log::error("MyFatoorah initiateSession error response: " . json_encode($data));
                return res_data($data['Message'] ?? 'Failed to initiate session', 'failed', 400);
            }

            return res_data($data['Data'], 'success', 200);
        } catch (\Exception $e) {
            Log::error("MyFatoorah initiateSession exception: " . $e->getMessage());
            return res_data('An error occurred while initiating session', 'failed', 500);
        }
    }

    /**
     * Initiate Payment (SendPayment)
     * Redirects users to the payment gateway
     */
    public function initiatePayment(Request $request)
    {
        try {
            $url = "{$this->baseUrl}/v2/SendPayment";
            $payload = [
                'PaymentMethodId' => 0, // 0 = All payment methods
                'InvoiceValue' => $request->amount,
                'CustomerName' => $request->customerName,
                'CustomerEmail' => $request->customerEmail ?? 'noreply@example.com',
                'CustomerMobile' => $request->customerPhone,
                'DisplayCurrencyIso' => 'KWD',
                'MobileCountryCode' => '+965',
                'CallBackUrl' => config('app.url') . '/payment/callback',
                'ErrorUrl' => config('app.url') . '/payment/failed',
                'Language' => 'AR',
                'CustomerReference' => $request->orderId,
                'InvoiceItems' => [
                    [
                        'ItemName' => $request->description ?? 'Payment',
                        'Quantity' => 1,
                        'UnitPrice' => $request->amount,
                    ],
                ],
                'UserDefinedField' => json_encode(array_merge([
                    'orderId' => $request->orderId,
                    'orderType' => $request->orderType,
                ], $request->metadata ?? [])),
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            $data = $response->json();

            if (!isset($data['IsSuccess']) || !$data['IsSuccess']) {
                Log::error("MyFatoorah initiatePayment error response: " . json_encode($data));
                throw new \Exception($data['Message'] ?? 'فشل في إنشاء الفاتورة');
            }

            return res_data([
                'invoiceId' => $data['Data']['InvoiceId'],
                'invoiceURL' => $data['Data']['InvoiceURL'],
                'customerReference' => $request->orderId,
            ], 'success', 200);
        } catch (\Exception $e) {
            Log::error("MyFatoorah initiatePayment error: " . $e->getMessage());
            return res_data($e->getMessage(), 'failed', 400);
        }
    }

    /**
     * Get Payment Status
     * Checks the status of a transaction
     */
    public function getPaymentStatus(Request $request)
    {
        try {
            $url = "{$this->baseUrl}/v2/GetPaymentStatus";
            $paymentId = $request->paymentId;
            $keyType = $request->keyType ?? 'PaymentId';

            $payload = [
                'Key' => $paymentId,
                'KeyType' => $keyType,
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            $data = $response->json();

            if (!isset($data['IsSuccess']) || !$data['IsSuccess']) {
                Log::error("MyFatoorah getPaymentStatus error response: " . json_encode($data));
                throw new \Exception($data['Message'] ?? 'فشل في جلب حالة الدفع');
            }

            $paymentData = $data['Data'];
            $invoiceStatus = $paymentData['InvoiceStatus'];

            $metadata = [];
            try {
                $metadata = json_decode($paymentData['UserDefinedField'] ?? '{}', true);
            } catch (\Exception $e) {
                Log::warning("Failed to parse UserDefinedField");
            }

            return res_data([
                'isPaid' => $invoiceStatus === 'Paid',
                'status' => $invoiceStatus,
                'invoiceId' => $paymentData['InvoiceId'],
                'invoiceReference' => $paymentData['InvoiceReference'],
                'customerReference' => $paymentData['CustomerReference'],
                'amount' => $paymentData['InvoiceValue'],
                'paidAmount' => $paymentData['InvoiceTransactions'][0]['TransactionValue'] ?? 0,
                'paymentMethod' => $paymentData['InvoiceTransactions'][0]['PaymentGateway'] ?? '',
                'transactionId' => $paymentData['InvoiceTransactions'][0]['TransactionId'] ?? '',
                'metadata' => $metadata,
                'rawData' => $paymentData,
            ], 'success', 200);
        } catch (\Exception $e) {
            Log::error("MyFatoorah getPaymentStatus error: " . $e->getMessage());
            return res_data($e->getMessage(), 'failed', 400);
        }
    }

    public function createMyFatoorahSession(Request $request)
    {
        $apiURL = "{$this->baseUrl}/v2/SendPayment";
        $apiKey = $this->apiKey;

        $postData = [
            'CustomerName'       => $request->customerName ?? 'Guest User',
            'NotificationOption' => 'LNK', // 'LNK' for link, 'ALL' for SMS/Email
            'InvoiceValue'       => $request->amount ?? 10,
            'DisplayCurrencyIso' => 'KWD',
            'CallBackUrl'        => route('myfatoorah.callback'),
            'ErrorUrl'           => route('myfatoorah.error'),
            'Language'           => 'ar',
            'CustomerReference'  => $request->orderId ?? uniqid(),
        ];

        $response = Http::withToken($apiKey)->post($apiURL, $postData);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['IsSuccess']) && $data['IsSuccess']) {
                return redirect($data['Data']['InvoiceURL']);
            }
            return res_data($data['Message'] ?? 'Payment initiation failed.', 'failed', 400);
        }

        return res_data('Payment request failed.', 'failed', 500);
    }

    /**
     * Send Payment (SendPayment)
     * Direct integration version based on user requirements
     */
    public function sendPayment(Request $request)
    {
        try {
            $url = "https://apitest.myfatoorah.com/v2/SendPayment";

            $payload = [
                'CustomerName'       => $request->CustomerName ?? 'Ahmed Mohamed',
                'NotificationOption' => $request->NotificationOption ?? 'LNK',
                'InvoiceValue'       => $request->InvoiceValue ?? '100',
                'DisplayCurrencyIso' => $request->DisplayCurrencyIso ?? 'SAR',
                'CallBackUrl'        => $request->CallBackUrl ?? config('app.url') . '/api/payment/callback',
                'ErrorUrl'           => $request->ErrorUrl ?? config('app.url') . '/api/payment/failed',
                'Language'           => $request->Language ?? 'ar',
                'CustomerEmail'      => $request->CustomerEmail ?? 'customer@example.com',
                'MobileCountryCode'  => $request->MobileCountryCode ?? '+966',
                'CustomerMobile'     => $request->CustomerMobile ?? '500000000'
            ];

            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer SK_KWT_vVZlnnAqu8jRByOWaRPNId4ShzEDNt256dvnjebuyzo52dXjAfRx2ixW5umjWSUx",
            ])->post($url, $payload);

            $data = $response->json();

            if ($response->successful() && isset($data['IsSuccess']) && $data['IsSuccess']) {
                return res_data($data, 'success', 200);
            }

            Log::error("MyFatoorah sendPayment error response: " . json_encode($data));
            return res_data($data['Message'] ?? ($data['ValidationErrors'][0]['Error'] ?? 'Payment initiation failed'), 'failed', $response->status() == 200 ? 400 : $response->status());
        } catch (\Exception $e) {
            Log::error("MyFatoorah sendPayment exception: " . $e->getMessage());
            return res_data('An error occurred while sending payment: ' . $e->getMessage(), 'failed', 500);
        }
    }
    public function paymentCallback(Request $request)
    {
        Log::info("Fawaterak paymentCallback hit", $request->all());
        $frontendRedirect = env('FRONTEND_URL', 'https://nartaqi-user.vercel.app');
        $invoiceId = $request->query('invoice_id') ?? $request->query('invoiceId'); // From Fawaterak

        if ($invoiceId) {
            $FAWATERAK_API_URL = 'https://app.fawaterk.com/api/v2';
            $API_KEY = "3fa3a46b49869715bf5a149e126468adb8dfbdc560decdd41e";

            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $API_KEY,
                    'Content-Type' => 'application/json'
                ])->get($FAWATERAK_API_URL . '/getInvoiceData/' . $invoiceId);

                Log::info("Fawaterak getInvoiceData response: " . $response->body());

                if ($response->successful()) {
                    $result = $response->json();

                    $invoiceStatus = $result['data']['status_text'] ?? ($result['data']['invoice_status'] ?? '');
                    $isPaid = strtolower($invoiceStatus) === 'paid' || strtolower($invoiceStatus) === 'success' || ($result['data']['paid'] ?? 0) == 1;

                    if ($isPaid) {
                        // Check different possible locations for payload
                        $payLoad = $result['data']['pay_load'] ?? ($result['data']['payload'] ?? ($result['data']['payLoad'] ?? []));
                        if (is_string($payLoad)) {
                            $payLoad = json_decode($payLoad, true) ?? [];
                        }

                        $studentId = $payLoad['student_id'] ?? null;
                        $roundId = $payLoad['round_id'] ?? null;
                        $type = $payLoad['type'] ?? 'single';

                        $couponCode = $payLoad['coupon_code'] ?? null;
                        if ($couponCode) {
                            $couponCode = strtoupper($couponCode);
                        }

                        if ($roundId) {
                            $frontendRedirect = rtrim($frontendRedirect, '/') . '/course/' . $roundId;
                        }

                        // Update payment transaction in DB
                        $isAlreadyPaid = false;
                        try {
                            $transaction = \Modules\Courses\Models\PaymentTransaction::where('invoice_id', $invoiceId)->first();
                            if ($transaction) {
                                $isAlreadyPaid = ($transaction->status === 'paid');
                                $transaction->update([
                                    'status' => 'paid',
                                    'raw_response' => array_merge((array)$transaction->raw_response, ['payment_callback' => $result])
                                ]);
                            }
                        } catch (\Exception $dbEx) {
                            Log::error('Failed to update PaymentTransaction in paymentCallback: ' . $dbEx->getMessage());
                        }

                        // Increment coupon usage if used
                        if ($couponCode && !$isAlreadyPaid) {
                            try {
                                $couponQuery = \Modules\Courses\Models\Coupon::where('code', $couponCode);
                                if ($transaction && $transaction->round_id) {
                                    $couponExists = (clone $couponQuery)->where('target', 'rounds')->where('round_id', $transaction->round_id)->first()
                                        ?? (clone $couponQuery)->where('target', 'rounds')->whereNull('round_id')->first();
                                } else {
                                    $couponExists = $couponQuery->where('target', 'store')->first();
                                }

                                if ($couponExists && !\Modules\Courses\Models\Coupon::where('id', $couponExists->id)->where('updated_at', '>=', now()->subMinutes(5))->exists()) {
                                    $couponExists->increment('used_count');
                                }
                            } catch (\Exception $e) {
                                Log::error('Failed to increment coupon in PaymentController callback: ' . $e->getMessage());
                            }
                        }

                        // // Manually enroll student if they are not already enrolled (DISABLED - ACTIVATION MOVED EXCLUSIVELY TO WEBHOOK)
                        // if ($studentId) {
                        //     if ($type === 'cart') {
                        //         $cartItems = \Modules\Cart\Models\Cart::where('student_id', $studentId)->get();
                        //         foreach ($cartItems as $item) {
                        //             if ($item->type === 'rounds') {
                        //                 $exists = \Modules\Courses\Models\UserRounds::where('student_id', $studentId)
                        //                     ->where('round_id', $item->item_id)
                        //                     ->first();
                        // 
                        //                 if (!$exists) {
                        //                     \Modules\Courses\Models\UserRounds::create([
                        //                         'student_id' => $studentId,
                        //                         'round_id' => $item->item_id,
                        //                         'status' => 'active',
                        //                         'end_date' => now()->addYears(1),
                        //                         'day' => now()->toDateString(),
                        //                         'time' => now()->toTimeString(),
                        //                         'payment_id' => $invoiceId,
                        //                     ]);
                        //                 } elseif ($exists->status !== 'active') {
                        //                     $exists->update([
                        //                         'status' => 'active',
                        //                         'payment_id' => $invoiceId
                        //                     ]);
                        //                 }
                        //             } elseif ($item->type === 'books') {
                        //                 $storeItem = \Modules\Store\Models\Store::with('books')->find($item->item_id);
                        //                 if ($storeItem) {
                        //                     $booksCount = $storeItem->books->count();
                        //                     foreach ($storeItem->books as $index => $book) {
                        //                         $displayName = $booksCount > 1 
                        //                             ? $storeItem->title . " - كتاب " . ($index + 1)
                        //                             : $storeItem->title;
                        // 
                        //                         $existsBook = \Modules\Store\Models\StudentBook::where('student_id', $studentId)
                        //                             ->where('store_id', $item->item_id)
                        //                             ->where('book_url', $book->book_url)
                        //                             ->first();
                        // 
                        //                         if (!$existsBook) {
                        //                           \Modules\Store\Models\StudentBook::create([
                        //                               'student_id' => $studentId,
                        //                               'store_id' => $item->item_id,
                        //                               'book_name' => $displayName,
                        //                               'book_url' => $book->book_url,
                        //                               'payment_id' => $invoiceId,
                        //                           ]);
                        //                         } else {
                        //                             $existsBook->update(['payment_id' => $invoiceId]);
                        //                         }
                        //                     }
                        //                 }
                        //             }
                        //         }
                        //         \Modules\Cart\Models\Cart::where('student_id', $studentId)->delete();
                        //     } elseif ($roundId) {
                        //         $exists = \Modules\Courses\Models\UserRounds::where('student_id', $studentId)
                        //             ->where('round_id', $roundId)
                        //             ->first();
                        // 
                        //         if (!$exists) {
                        //             \Modules\Courses\Models\UserRounds::create([
                        //                 'student_id' => $studentId,
                        //                 'round_id' => $roundId,
                        //                 'status' => 'active',
                        //                 'end_date' => now()->addYears(1),
                        //                 'day' => now()->toDateString(),
                        //                 'time' => now()->toTimeString(),
                        //                 'payment_id' => $invoiceId,
                        //             ]);
                        //         } elseif ($exists->status !== 'active') {
                        //             $exists->update([
                        //                 'status' => 'active',
                        //                 'payment_id' => $invoiceId
                        //             ]);
                        //         }
                        //     }
                        // }
                    }
                }
            } catch (\Exception $e) {
                Log::error("[PaymentCallback Fawaterak] " . $e->getMessage());
            }
        }

        // Handle successful payment logic here
        $platform = $request->query('platform', 'web');
        return view('courses::payment.success', compact('frontendRedirect', 'platform'));
    }

    public function paymentFailed(Request $request)
    {
        $frontendRedirect = env('FRONTEND_URL', 'https://nartaqi-user.vercel.app');
        $invoiceId = $request->query('invoice_id') ?? $request->query('invoiceId');

        if ($invoiceId) {
            $FAWATERAK_API_URL = 'https://app.fawaterk.com/api/v2';
            $API_KEY = "3fa3a46b49869715bf5a149e126468adb8dfbdc560decdd41e";

            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $API_KEY,
                    'Content-Type' => 'application/json'
                ])->get($FAWATERAK_API_URL . '/getInvoiceData/' . $invoiceId);

                if ($response->successful()) {
                    $result = $response->json();

                    $payLoad = $result['data']['pay_load'] ?? ($result['data']['payload'] ?? ($result['data']['payLoad'] ?? []));
                    if (is_string($payLoad)) {
                        $payLoad = json_decode($payLoad, true) ?? [];
                    }

                    $roundId = $payLoad['round_id'] ?? null;

                    if ($roundId) {
                        $frontendRedirect = rtrim($frontendRedirect, '/') . '/course/' . $roundId;
                    }

                    // Update payment transaction in DB as failed
                    try {
                        $transaction = \Modules\Courses\Models\PaymentTransaction::where('invoice_id', $invoiceId)->first();
                        if ($transaction) {
                            $transaction->update([
                                'status' => 'failed',
                                'raw_response' => array_merge((array)$transaction->raw_response, ['payment_failed' => $result])
                            ]);
                        }
                    } catch (\Exception $dbEx) {
                        Log::error('Failed to update PaymentTransaction in paymentFailed: ' . $dbEx->getMessage());
                    }
                }
            } catch (\Exception $e) {
                Log::error("[PaymentFailed Fawaterak] " . $e->getMessage());
            }
        }

        $platform = $request->query('platform', 'web');
        return view('courses::payment.failed', compact('frontendRedirect', 'platform'));
    }
}
