<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use Modules\Courses\Models\UserRounds;
use Modules\Courses\Models\Rounds;
use Modules\Authentication\Models\Student;
use Modules\Cart\Models\Cart;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreBook;
use Modules\Store\Models\StudentBook;
use Modules\Courses\Models\Coupon;

require_once base_path('Modules/Authentication/smsfile.php');

class FawaterakWebhookController extends Controller
{
    /**
     * Handle Fawaterak Webhook update.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        try {
            $body = $request->all();
            Log::info("Fawaterak Webhook Full Body:", $body);

            if (empty($body)) {
                return response()->json([
                    "success" => false,
                    "message" => "Invalid JSON"
                ], 400);
            }

            $invoiceId = $body['invoice_id'] ?? null;
            $invoiceStatus = $body['invoice_status'] ?? ($body['status'] ?? ($body['status_text'] ?? null));
            $payLoad = $body['pay_load'] ?? ($body['payload'] ?? ($body['payLoad'] ?? null));

            Log::info("[Fawaterak Webhook] Received update for Invoice: " . $invoiceId . ", Status: " . $invoiceStatus);

            if (!$invoiceId || !$invoiceStatus) {
                return response()->json([
                    "success" => false,
                    "message" => "Missing data"
                ], 400);
            }

            // Decode payload if it's sent as a JSON string
            if (is_string($payLoad)) {
                $decodedPayload = json_decode($payLoad, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $payLoad = $decodedPayload;
                }
            }

            $studentId = $payLoad['student_id'] ?? null;
            $roundId = $payLoad['round_id'] ?? null;
            $type = $payLoad['type'] ?? 'single';
            $currency = $payLoad['currency'] ?? 'SAR';
            $couponCode = $payLoad['coupon_code'] ?? null;
            if ($couponCode) {
                $couponCode = strtoupper($couponCode);
            }

            if (!$studentId) {
                Log::error("[Webhook Error] Student ID not found in payload for Invoice: " . $invoiceId);
                return response()->json(["success" => false, "message" => "Student ID missing"], 200);
            }

            // Update transaction record in DB
            $isAlreadyPaid = false;
            try {
                $transaction = \Modules\Courses\Models\PaymentTransaction::where('invoice_id', $invoiceId)->first();
                $dbStatus = strtolower($invoiceStatus) === 'paid' ? 'paid' : strtolower($invoiceStatus);
                
                if ($transaction) {
                    $isAlreadyPaid = ($transaction->status === 'paid');
                    $transaction->update([
                        'status' => $dbStatus,
                        'raw_response' => array_merge((array)$transaction->raw_response, ['webhook_update' => $body])
                    ]);
                } else {
                    \Modules\Courses\Models\PaymentTransaction::create([
                        'invoice_id' => $invoiceId,
                        'student_id' => $studentId,
                        'payment_method' => 'Fawaterak',
                        'amount' => $body['invoice_value'] ?? 0,
                        'currency' => $currency,
                        'status' => $dbStatus,
                        'type' => $type,
                        'round_id' => $roundId,
                        'pay_load' => $payLoad,
                        'raw_response' => ['webhook_creation' => $body]
                    ]);
                }
            } catch (Throwable $dbEx) {
                Log::error("Failed to update/create PaymentTransaction in Webhook: " . $dbEx->getMessage());
            }

            // Process based on payment status
            if (strtolower($invoiceStatus) === "paid") {
                // Increment coupon usage if used, but only if we didn't already process this payment
                if ($couponCode && !$isAlreadyPaid) {
                    try {
                        $couponQuery = Coupon::where('code', $couponCode);
                        if ($transaction && $transaction->round_id) {
                            $coupon = (clone $couponQuery)->where('target', 'rounds')->where('round_id', $transaction->round_id)->first()
                                ?? (clone $couponQuery)->where('target', 'rounds')->whereNull('round_id')->first();
                        } else {
                            $coupon = $couponQuery->where('target', 'store')->first();
                        }
                        if ($coupon) {
                            $coupon->increment('used_count');
                            Log::info("[Webhook] Incremented usage for coupon ID {$coupon->id}: $couponCode");
                        } else {
                            Log::warning("[Webhook] Coupon code $couponCode not found for incrementing");
                        }
                    } catch (\Throwable $e) {
                        Log::error("[Webhook] Failed to increment coupon: " . $e->getMessage());
                    }
                }

                if ($type === 'cart') {
                    // Handle entire cart payment (Books, Bags, Accessories)
                    $cartItems = Cart::where('student_id', $studentId)->get();
                    
                    foreach ($cartItems as $item) {
                        if ($item->type === 'rounds') {
                            $this->enrollStudent($studentId, $item->item_id, $invoiceId);
                        } elseif ($item->type === 'books') {
                            $this->processBookPurchase($studentId, $item->item_id, $invoiceId);
                        }
                        // Note: handle bags and accessories here if you have a table for them
                    }

                    // Clear the cart after successful payment
                    Cart::where('student_id', $studentId)->delete();
                    Log::info("[Webhook] Cleared cart for student $studentId after payment $invoiceId");

                } else {
                    // Handle single course payment
                    if (!$roundId) {
                        Log::error("[Webhook Error] Round ID missing for single payment. Invoice: " . $invoiceId);
                        return response()->json(["success" => false, "message" => "Round ID missing"], 200);
                    }
                    $this->enrollStudent($studentId, $roundId, $invoiceId);
                }
            } else {
                Log::warning("[Webhook] Payment for Invoice $invoiceId is not successful (Status: $invoiceStatus)");
            }

            return response()->json([
                "success" => true
            ], 200);

        } catch (Throwable $e) {
            Log::error("[Fawaterak Webhook Error] " . $e->getMessage(), [
                'exception' => $e,
                'request_body' => $request->all()
            ]);

            return response()->json([
                "success" => false,
                "message" => "Server error"
            ], 500);
        }
    }

    /**
     * Handle immediate redirection from Fawaterak to verify payment and redirect to frontend.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function successCallback(Request $request)
    {
        $invoiceId = $request->query('invoice_id');
        $frontendRedirect = $request->query('frontend_redirect', env('FRONTEND_URL', 'https://nartaqi-user.vercel.app'));

        if (!$invoiceId) {
            return view('courses::payment.success', compact('frontendRedirect'));
        }

        $FAWATERAK_API_URL = 'https://app.fawaterk.com/api/v2';
        $API_KEY = "3fa3a46b49869715bf5a149e126468adb8dfbdc560decdd41e";

        try {
            // Get invoice data to verify payment
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $API_KEY,
                'Content-Type' => 'application/json'
            ])->get($FAWATERAK_API_URL . '/getInvoiceData/' . $invoiceId);

            if ($response->successful()) {
                $result = $response->json();
                
                if (isset($result['data']) && strtolower($result['data']['status']) === 'paid') {
                    $payLoad = $result['data']['payload'] ?? [];
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

                    if ($studentId) {
                        // Update payment transaction
                        $isAlreadyPaid = false;
                        try {
                            $transaction = \Modules\Courses\Models\PaymentTransaction::where('invoice_id', $invoiceId)->first();
                            if ($transaction) {
                                $isAlreadyPaid = ($transaction->status === 'paid');
                                $transaction->update([
                                    'status' => 'paid',
                                    'raw_response' => array_merge((array)$transaction->raw_response, ['success_callback' => $result])
                                ]);
                            }
                        } catch (Throwable $dbEx) {
                            Log::error("Failed to update PaymentTransaction in successCallback: " . $dbEx->getMessage());
                        }

                        // Increment coupon usage if used
                        if ($couponCode && !$isAlreadyPaid) {
                            $couponQuery = Coupon::where('code', $couponCode);
                            if ($roundId) {
                                $couponExists = (clone $couponQuery)->where('target', 'rounds')->where('round_id', $roundId)->first()
                                    ?? (clone $couponQuery)->where('target', 'rounds')->whereNull('round_id')->first();
                            } else {
                                $couponExists = $couponQuery->where('target', 'store')->first();
                            }

                            if ($couponExists && !Coupon::where('id', $couponExists->id)->where('updated_at', '>=', now()->subMinutes(5))->exists()) {
                                // Basic safeguard against incrementing multiple times in a short window
                                // Although webhook might also increment, this is acceptable for now.
                                $couponExists->increment('used_count');
                            }
                        }

                        if ($type === 'cart') {
                            $cartItems = Cart::where('student_id', $studentId)->get();
                            foreach ($cartItems as $item) {
                                if ($item->type === 'rounds') {
                                    $this->enrollStudent($studentId, $item->item_id, $invoiceId);
                                } elseif ($item->type === 'books') {
                                    $this->processBookPurchase($studentId, $item->item_id, $invoiceId);
                                }
                            }
                            Cart::where('student_id', $studentId)->delete();
                        } else if ($roundId) {
                            $this->enrollStudent($studentId, $roundId, $invoiceId);
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            Log::error("[Fawaterak Success Callback Error] " . $e->getMessage());
        }

        return view('courses::payment.success', compact('frontendRedirect'));
    }

    private function processBookPurchase($studentId, $storeItemId, $invoiceId)
    {
        $storeItem = Store::with('books')->find($storeItemId);
        if (!$storeItem) return;

        $booksCount = $storeItem->books->count();

        foreach ($storeItem->books as $index => $book) {
            $displayName = $booksCount > 1 
                ? $storeItem->title . " - كتاب " . ($index + 1)
                : $storeItem->title;

            // Check if student already has this book
            $exists = StudentBook::where('student_id', $studentId)
                ->where('store_id', $storeItemId)
                ->where('book_url', $book->book_url)
                ->first();

            if (!$exists) {
                StudentBook::create([
                    'student_id' => $studentId,
                    'store_id'   => $storeItemId,
                    'book_name'  => $displayName,
                    'book_url'   => $book->book_url,
                    'payment_id' => $invoiceId,
                ]);
                Log::info("[Webhook] Linked book {$book->id} to student $studentId for payment $invoiceId");
            } else {
                $exists->update(['payment_id' => $invoiceId]);
            }
        }
    }

    /**
     * Enroll student in a round.
     */
    private function enrollStudent($studentId, $roundId, $invoiceId)
    {
        // Check if the student is already enrolled in this round
        $exists = UserRounds::where('student_id', $studentId)
            ->where('round_id', $roundId)
            ->first();

        if (!$exists) {
            // Create a new enrollment record
            $userRound = UserRounds::create([
                'student_id' => $studentId,
                'round_id' => $roundId,
                'status' => 'active',
                'end_date' => now()->addYears(1),
                'day' => now()->toDateString(),
                'time' => now()->toTimeString(),
                'payment_id' => $invoiceId,
            ]);

            Log::info("[Webhook] Enrolled student $studentId in round $roundId after payment $invoiceId");

            // Send congratulatory WhatsApp notification
            $this->sendNotification($studentId, $roundId);
        } else if ($exists->status !== 'active') {
            // If already exists but not active, activate it
            $exists->update([
                'status' => 'active',
                'payment_id' => $invoiceId
            ]);
            Log::info("[Webhook] Activated existing enrollment for student $studentId in round $roundId");
        }
    }

    /**
     * Send congratulatory WhatsApp message.
     */
    private function sendNotification($studentId, $roundId)
    {
        try {
            $student = Student::find($studentId);
            $round = Rounds::find($roundId);

            if ($student && $round) {
                $studentName = $student->name;
                $roundName = $round->name;
                $phone = $student->phone;

                $smsMessage = "أهلاً بك يا $studentName\nتهانينا! لقد تم اشتراكك بنجاح في دورة: $roundName\nنتمنى لك رحلة تعليمية ممتعة ومفيدة مع منصة نرتقي.";
                
                sendWawpMessage($phone, $smsMessage);
            }
        } catch (Throwable $e) {
            Log::error("[Webhook SMS Error] " . $e->getMessage());
        }
    }
}
