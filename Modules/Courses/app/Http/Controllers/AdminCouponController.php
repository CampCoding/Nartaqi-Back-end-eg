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
        $coupons = Coupon::with(['round', 'rounds'])->orderBy('created_at', 'desc')->get();
        return res_data($coupons, 'success', 200);
    }

    /**
     * List course coupons.
     */
    public function roundsList()
    {
        $coupons = Coupon::with(['round', 'rounds'])
            ->where('target', 'rounds')
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
        $roundIds = $this->resolveRoundIds($request);

        $request->validate([
            'code' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($code, $target, $roundIds) {
                    if ($this->couponCodeConflicts($code, $target, $roundIds)) {
                        $fail('كود الخصم مأخوذ بالفعل لهذا الهدف أو الدورة.');
                    }
                }
            ],
            'type' => 'required|in:fixed,percentage',
            'target' => 'required|in:rounds,store',
            'value' => 'required|numeric|min:0',
            'round_id' => 'nullable|exists:rounds,id',
            'round_ids' => 'nullable|array',
            'round_ids.*' => 'integer|exists:rounds,id',
            'usage_limit' => 'nullable|integer|min:0',
            'expiry_date' => 'nullable|date|after:today',
        ]);

        if ($target === 'rounds' && empty($roundIds)) {
            return res_data(null, 'يجب اختيار دورة واحدة على الأقل', 422);
        }

        $coupon = Coupon::create([
            'code' => $code,
            'type' => $request->type,
            'target' => $request->target,
            'value' => $request->value,
            'round_id' => $roundIds[0] ?? null,
            'usage_limit' => $request->usage_limit ?? 0,
            'expiry_date' => $request->expiry_date,
            'is_active' => true,
        ]);

        if ($target === 'rounds') {
            $coupon->rounds()->sync($roundIds);
        }

        $coupon->load(['round', 'rounds']);

        return res_data($coupon, 'تم إضافة الكوبون بنجاح', 201);
    }

    /**
     * Update a coupon.
     */
    public function update(Request $request)
    {
        $id = $request->coupon_id ?? $request->id;
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return res_data(null, 'كود الخصم غير موجود أو تم حذفه مسبقاً', 404);
        }

        $code = strtoupper($request->code ?? $coupon->code);
        $target = $request->target ?? $coupon->target;
        $roundIds = $request->has('round_ids') || $request->has('round_id')
            ? $this->resolveRoundIds($request)
            : $coupon->round_ids;

        $request->validate([
            'code' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($id, $code, $target, $roundIds) {
                    if ($this->couponCodeConflicts($code, $target, $roundIds, $id)) {
                        $fail('كود الخصم مأخوذ بالفعل لهذا الهدف أو الدورة.');
                    }
                }
            ],
            'type' => 'nullable|in:fixed,percentage',
            'target' => 'nullable|in:rounds,store',
            'value' => 'nullable|numeric|min:0',
            'round_id' => 'nullable|exists:rounds,id',
            'round_ids' => 'nullable|array',
            'round_ids.*' => 'integer|exists:rounds,id',
            'usage_limit' => 'nullable|integer|min:0',
            'expiry_date' => 'nullable|date',
            'is_active' => 'nullable|boolean'
        ]);

        $coupon->update([
            'code' => $code,
            'type' => $request->type ?? $coupon->type,
            'target' => $target,
            'value' => $request->has('value') ? $request->value : $coupon->value,
            'round_id' => $target === 'rounds' ? ($roundIds[0] ?? null) : null,
            'usage_limit' => $request->has('usage_limit') ? ($request->usage_limit ?? 0) : $coupon->usage_limit,
            'expiry_date' => $request->has('expiry_date') ? $request->expiry_date : $coupon->expiry_date,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $coupon->is_active,
        ]);

        if ($target === 'rounds') {
            $coupon->rounds()->sync($roundIds);
        } else {
            $coupon->rounds()->sync([]);
        }

        $coupon->load(['round', 'rounds']);

        return res_data($coupon, 'تم تحديث الكوبون بنجاح', 200);
    }

    /**
     * Delete a coupon.
     */
    public function destroy(Request $request)
    {
        $id = $request->coupon_id ?? $request->id;
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return res_data(null, 'كود الخصم غير موجود أو تم حذفه مسبقاً', 404);
        }

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
        $coupon = Coupon::with(['round', 'rounds'])->where('code', $code)->first();

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

    private function resolveRoundIds(Request $request): array
    {
        $ids = collect($request->input('round_ids', []));
        if ($request->filled('round_id')) {
            $ids->push($request->round_id);
        }

        return $ids
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function couponCodeConflicts(string $code, ?string $target, array $roundIds, $ignoreId = null): bool
    {
        // For round-targeted coupons, courses can be freely attached to multiple coupons/codes
        if ($target === 'rounds') {
            return false;
        }

        $query = Coupon::where('code', $code)->where('target', $target);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
