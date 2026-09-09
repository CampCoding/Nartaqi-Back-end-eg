<?php

namespace Modules\Cart\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Cart\Models\Cart;
use App\Http\Controllers\Controller;
use Modules\Cart\Http\Requests\CartRequest;
use Modules\Courses\Models\Rounds;
use Modules\Courses\Models\TeachersModel;
use Modules\Store\Models\Store;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function user_cart(Request $request)
    {
        $student = $request->user();

        if (!$student) {
            return res_data('الطالب غير موجود', 'faild', 404);
        }

        $cartItems = Cart::where('student_id', $student->id)
        ->get()
        ->map(function ($item) {

        if ($item->type === 'rounds') {
            $item->load([
                'round' => function ($q) {
                    $q->with(['teacher', 'course_categories'])
                      ->withAvg('students_rates', 'rate');
                }
            ]);
            unset($item->store);
        } else {
            $item->load([
                'store' => function ($q) {
                    $q->select('id', 'title', 'description','price', 'image', 'category');
                }
            ]);

        unset($item->round);
        }

        return $item;

        });

        return res_data($cartItems, 'success', 200);
    }


    private function validateItem($type, $itemId)
    {
        if ($type === 'rounds') {
            return Rounds::where('id', $itemId)->exists();
        }

        return Store::where('id', $itemId)
            ->where('category', $type)
            ->exists();
    }

    public function cart_toggle(CartRequest $request)
    {
        $student = $request->user();

        if (!$student) {
            return res_data('الطالب غير موجود', 'failed', 404);
        }

        if (!$this->validateItem($request->type, $request->item_id)) {
            return res_data('العنصر غير موجود', 'failed', 404);
        }

        $cartItem = Cart::where([
            'item_id' => $request->item_id,
            'student_id' => $student->id,
            'type' => $request->type,
        ])->first();

        if (!$request->has('quantity') || $request->quantity === null) {
            if ($cartItem) {
                $cartItem->delete();
                return res_data('تمت إزالة العنصر من السلة بنجاح', 'success', 200);
            }
            return res_data('العنصر غير موجود في السلة', 'failed', 404);
        }

        if ($request->quantity < 1) {
            return res_data('الكمية يجب أن تكون أكبر من صفر', 'failed', 400);
        }

        if ($cartItem) {
            $cartItem->update([
                'quantity' => $request->quantity
            ]);

            return res_data('تم تحديث الكمية بنجاح', 'success', 200);
        }

        Cart::create([
            'student_id' => $student->id,
            'item_id' => $request->item_id,
            'quantity' => $request->quantity,
            'type' => $request->type
        ]);

        $itemType = $request->type === 'rounds' ? 'الدورة' : 'المنتج';

        return res_data("تمت إضافة {$itemType} إلى السلة بنجاح", 'success', 201);
    }

    public function delete_cart_item(CartRequest $request)
    {
        $student = $request->user();
        if(!$student){
            return res_data('الطالب غير موجود', 'failed' , 400);
        }

        $item = Cart::where('item_id', $request->item_id)
                    ->where('student_id', $student->id)
                    ->where('type', $request->type)
                    ->first();

        if (!$item) {
            return res_data('العنصر غير موجود في السلة', 'failed' , 400);
        }

        $item->delete();

        return res_data('تم حذف العنصر من السلة بنجاح', 'success' ,200);
    }

    public function delete_cart(Request $request)
    {
        $student = $request->user();
        if(!$student){
            return res_data('الطالب غير موجود', 'failed' , 400);
        }

        Cart::where('student_id', $student->id)->delete();

        return res_data('تم حذف جميع العناصر من السلة بنجاح', 'success', 200);
    }
}
