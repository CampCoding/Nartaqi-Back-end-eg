<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\Coupon;
use Modules\Courses\Models\Rounds;
use Modules\Cart\Models\Cart;

class CheckCouponController extends Controller
{
    /**
     * Check if a coupon is valid for a round or the store.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'target' => 'required|in:rounds,store',
            'round_id' => 'required_if:target,rounds|exists:rounds,id'
        ]);

        $code = strtoupper($request->code);
        $target = $request->target;
        $roundId = $request->round_id;

        $couponQuery = Coupon::where('code', $code);

        if ($target === 'rounds') {
            $coupon = (clone $couponQuery)
                ->where('target', 'rounds')
                ->where('round_id', $roundId)
                ->first() 
                ?? (clone $couponQuery)
                ->where('target', 'rounds')
                ->whereNull('round_id')
                ->first();
        } else {
            $coupon = $couponQuery->where('target', 'store')->first();
        }

        if (!$coupon) {
            return res_data('كود الخصم غير صحيح', 'failed', 404);
        }

        if (!$coupon->isValidFor($target, $roundId)) {
            $msg = ($target === 'rounds') 
                ? 'كود الخصم غير متاح لهذه الدورة أو انتهت صلاحيته' 
                : 'كود الخصم غير متاح للمتجر أو انتهت صلاحيته';
            
            if ($request->has('debug') || $request->input('debug')) {
                return response()->json([
                    'statusCode' => 400,
                    'status' => 'failed',
                    'message' => $msg,
                    'debug' => [
                        'coupon' => [
                            'code' => $coupon->code,
                            'is_active' => $coupon->is_active,
                            'expiry_date' => $coupon->expiry_date ? $coupon->expiry_date->toIso8601String() : null,
                            'expiry_date_raw' => $coupon->getRawOriginal('expiry_date'),
                            'usage_limit' => $coupon->usage_limit,
                            'used_count' => $coupon->used_count,
                            'target' => $coupon->target,
                            'round_id' => $coupon->round_id,
                        ],
                        'request' => [
                            'target' => $target,
                            'round_id' => $roundId,
                        ],
                        'checks' => [
                            'is_active_check_failed' => !$coupon->is_active,
                            'expiry_check_failed' => $coupon->expiry_date && $coupon->expiry_date->isPast() ? true : false,
                            'limit_check_failed' => $coupon->usage_limit > 0 && $coupon->used_count >= $coupon->usage_limit,
                            'target_check_failed' => $coupon->target !== $target,
                            'round_id_check_failed' => ($target === 'rounds' && $coupon->round_id && $coupon->round_id != $roundId) ? true : false,
                        ]
                    ]
                ], 400);
            }
            return res_data($msg, 'failed', 400);
        }

        // Calculate discount based on target
        if ($target === 'rounds') {
            $round = Rounds::find($roundId);
            $originalPrice = $round->price;
        } else {
            // For store, we check the user's cart total
            $student = $request->user();
            if (!$student) {
                return res_data('يجب تسجيل الدخول لفحص خصم المتجر', 'failed', 401);
            }
            $originalPrice = Cart::where('student_id', $student->id)->get()->sum(function($item) {
                $item->load($item->type === 'rounds' ? 'round' : 'store');
                $price = ($item->type === 'rounds') ? ($item->round->price ?? 0) : ($item->store->price ?? 0);
                return $price * $item->quantity;
            });
        }

        $discountAmount = $coupon->getDiscountAmount($originalPrice);
        $finalPrice = max(0, $originalPrice - $discountAmount);

        return res_data([
            'coupon_code' => $coupon->code,
            'target' => $coupon->target,
            'discount_type' => $coupon->type,
            'discount_value' => $coupon->value,
            'discount_amount' => $discountAmount,
            'original_price' => $originalPrice,
            'final_price' => $finalPrice
        ], 'success', 200);
    }
}
