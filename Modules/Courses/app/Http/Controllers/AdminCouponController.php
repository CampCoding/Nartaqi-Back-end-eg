<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\Coupon;

class AdminCouponController extends Controller
{
    /**
     * List all coupons.
     */
    public function index()
    {
        $coupons = Coupon::with('round')->orderBy('created_at', 'desc')->get();
        return res_data($coupons, 'success', 200);
    }

    /**
     * List course coupons.
     */
    public function roundsList()
    {
        $coupons = Coupon::where('target', 'rounds')
            ->orderBy('created_at', 'desc')
            ->get();
        return res_data($coupons, 'success', 200);
    }

    /**
     * List store coupons.
     */
    public function storeList()
    {
        $coupons = Coupon::where('target', 'store')
            ->orderBy('created_at', 'desc')
            ->get();
        return res_data($coupons, 'success', 200);
    }

    /**
     * Store a new coupon.
     */
    public function store(Request $request)
    {
        $code = strtoupper($request->code);
        $target = $request->target;
        $roundId = $request->round_id;

        $request->validate([
            'code' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($code, $target, $roundId) {
                    $query = Coupon::where('code', $code)->where('target', $target);
                    if ($target === 'rounds') {
                        $query->where('round_id', $roundId);
                    } else {
                        $query->whereNull('round_id');
                    }
                    if ($query->exists()) {
                        $fail('كود الخصم مأخوذ بالفعل لهذا الهدف أو الدورة.');
                    }
                }
            ],
            'type' => 'required|in:fixed,percentage',
            'target' => 'required|in:rounds,store',
            'value' => 'required|numeric|min:0',
            'round_id' => 'nullable|required_if:target,rounds|exists:rounds,id',
            'usage_limit' => 'nullable|integer|min:0',
            'expiry_date' => 'nullable|date|after:today',
        ]);

        $coupon = Coupon::create([
            'code' => $code,
            'type' => $request->type,
            'target' => $request->target,
            'value' => $request->value,
            'round_id' => $request->round_id,
            'usage_limit' => $request->usage_limit ?? 0,
            'expiry_date' => $request->expiry_date,
            'is_active' => true,
        ]);

        return res_data($coupon, 'تم إضافة الكوبون بنجاح', 201);
    }

    /**
     * Update a coupon.
     */
    public function update(Request $request)
    {
        $id = $request->id;
        $coupon = Coupon::findOrFail($id);

        $code = strtoupper($request->code ?? $coupon->code);
        $target = $request->target ?? $coupon->target;
        $roundId = $request->has('round_id') ? $request->round_id : $coupon->round_id;

        $request->validate([
            'id' => 'required|exists:coupons,id',
            'code' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($id, $code, $target, $roundId) {
                    $query = Coupon::where('code', $code)
                        ->where('target', $target)
                        ->where('id', '!=', $id);
                    if ($target === 'rounds') {
                        $query->where('round_id', $roundId);
                    } else {
                        $query->whereNull('round_id');
                    }
                    if ($query->exists()) {
                        $fail('كود الخصم مأخوذ بالفعل لهذا الهدف أو الدورة.');
                    }
                }
            ],
            'type' => 'nullable|in:fixed,percentage',
            'target' => 'nullable|in:rounds,store',
            'value' => 'nullable|numeric|min:0',
            'round_id' => 'nullable|exists:rounds,id',
            'usage_limit' => 'nullable|integer|min:0',
            'expiry_date' => 'nullable|date',
            'is_active' => 'nullable|boolean'
        ]);

        $coupon->update($request->all());

        return res_data($coupon, 'تم تحديث الكوبون بنجاح', 200);
    }

    /**
     * Delete a coupon.
     */
    public function destroy(Request $request)
    {
        $id = $request->id;
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return res_data(null, 'تم حذف الكوبون بنجاح', 200);
    }

    /**
     * Get usage report for a specific coupon.
     */
    public function usageReport(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = strtoupper($request->code);
        $coupon = Coupon::with('round')->where('code', $code)->first();

        if (!$coupon) {
            return res_data(null, 'كود الخصم غير موجود', 404);
        }

        // Fetch successful payment transactions where coupon_code is in pay_load
        $transactions = \Modules\Courses\Models\PaymentTransaction::with(['student', 'round'])
            ->where('status', 'paid')
            ->where('pay_load->coupon_code', $code)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalRevenue = 0;
        $usages = [];

        foreach ($transactions as $tx) {
            $amount = floatval($tx->amount);
            $totalRevenue += $amount;

            $itemType = $tx->type ?? 'single'; // single / cart
            
            $usages[] = [
                'transaction_id' => $tx->id,
                'invoice_id' => $tx->invoice_id,
                'amount' => $amount,
                'currency' => $tx->currency,
                'used_at' => $tx->created_at ? $tx->created_at->toDateTimeString() : null,
                'student' => $tx->student ? [
                    'id' => $tx->student->id,
                    'name' => $tx->student->name,
                    'email' => $tx->student->email,
                    'phone' => $tx->student->phone,
                ] : null,
                'item' => [
                    'type' => $itemType,
                    'round_id' => $tx->round_id,
                    'round_title' => $tx->round ? ($tx->round->name ?? $tx->round->title) : null,
                ]
            ];
        }

        $summary = [
            'total_successful_usages' => count($usages),
            'total_amount_received' => $totalRevenue,
        ];

        return res_data([
            'coupon' => $coupon,
            'summary' => $summary,
            'usages' => $usages,
        ], 'تم جلب تقرير استخدام الكوبون بنجاح', 200);
    }
}
