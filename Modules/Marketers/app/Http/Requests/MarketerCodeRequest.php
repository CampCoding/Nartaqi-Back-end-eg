<?php

namespace Modules\Marketers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MarketerCodeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */

    public function rules(): array
    {

        $rules = [
            'status' => 'required|in:approved,suspended,pending',
            'marketer_id' => 'required|exists:marketers,id',
        ];

        if ($this->input('status') !== 'rejected') {
            $rules = array_merge($rules, [
                'discount_percentage' => 'required|numeric|min:0|max:100',
                'commission_percentage' => 'required|numeric|min:0|max:100',
            ]);
        }

        return $rules;
    }


    public function messages(): array
    {
        return [
            'marketer_id.required' => 'معرف المسوق مطلوب.',
            'marketer_id.exists' => 'المسوق غير موجود.',
            'code.unique' => 'كود الخصم مستخدم بالفعل.',
            'discount_percentage.required' => 'نسبة الخصم مطلوبة.',
            'discount_percentage.numeric' => 'نسبة الخصم يجب أن تكون رقمًا.',
            'discount_percentage.min' => 'نسبة الخصم لا يمكن أن تكون أقل من 0.',
            'discount_percentage.max' => 'نسبة الخصم لا يمكن أن تكون أكثر من 100.',
            'commission_percentage.required' => 'نسبة العمولة مطلوبة.',
            'commission_percentage.numeric' => 'نسبة العمولة يجب أن تكون رقمًا.',
            'commission_percentage.min' => 'نسبة العمولة لا يمكن أن تكون أقل من 0.',
            'commission_percentage.max' => 'نسبة العمولة لا يمكن أن تكون أكثر من 100.',
        ];
    }

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
