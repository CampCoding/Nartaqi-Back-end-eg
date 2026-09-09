<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateInvoiceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'orderId' => 'nullable',
            'student_id' => 'required|exists:students,id',
            'round_id' => 'required|exists:rounds,id',
            'currency' => 'nullable|string|max:10',
            'userData' => 'required|array',
            'userData.full_name' => 'required|string|max:255',
            'userData.email' => 'nullable|email|max:255',
            'userData.phone' => 'required|string|max:20',
            'total_price' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'regionPrice' => 'nullable|numeric',
            'copounDiscount' => 'nullable|numeric',
            'copounData' => 'nullable|array',
            'items.*.productData' => 'required|array',
            'items.*.productData.name' => 'required|string|max:255',
            'items.*.priceAfterDiscount' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'regionPrice' => 'nullable|numeric|min:0',
            'copounDiscount' => 'nullable|numeric|min:0',
            'copounData' => 'nullable|array',
            'copounData.code' => 'nullable|string|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'userData.required' => 'بيانات المستخدم مطلوبة',
            'userData.full_name.required' => 'الاسم الكامل مطلوب',
            'userData.email.required' => 'البريد الإلكتروني مطلوب',
            'userData.email.email' => 'البريد الإلكتروني غير صالح',
            'userData.phone.required' => 'رقم الهاتف مطلوب',
            'total_price.required' => 'السعر الإجمالي مطلوب',
            'items.required' => 'العناصر مطلوبة',
            'items.array' => 'العناصر يجب أن تكون مصفوفة',
            'items.*.productData.name.required' => 'اسم المنتج مطلوب لكل عنصر',
            'items.*.priceAfterDiscount.required' => 'السعر بعد الخصم مطلوب لكل عنصر',
            'items.*.quantity.required' => 'الكمية مطلوبة لكل عنصر',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first(),
        ], 422));
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
