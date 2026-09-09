<?php

namespace Modules\Cart\Http\Requests;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CartRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'item_id' => 'required|integer',
            'quantity' => 'sometimes|integer|min:1',
            'type' => 'sometimes|in:rounds,books,accessories,bags',
        ];
    }
            public function messages(): array
    {
        return [
            'item_id.required' => 'المعرف مطلوب',
            'item_id.integer' => 'المعرف يجب ان يكون رقم صحيح',
            'quantity.integer' => 'الكميه يجب ان تكون رقم صحيح',
            'quantity.min' => 'الكميه يجب ان لا تقل عن 1',
            'type.in' => 'نوع العنصر غير صالح',
        ];
    }


        protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first(),
        ], 422));
    }

        public function wantsJson(): bool
    {
        return true;
    }
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
