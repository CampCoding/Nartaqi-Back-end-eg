<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendCompletionRatesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'round_id' => 'required|integer|exists:rounds,id',
            'threshold' => 'required|numeric|min:0|max:100',
            'message' => 'required|string|max:4000',
        ];
    }

    public function messages(): array
    {
        return [
            'round_id.required' => 'يجب اختيار الدورة',
            'round_id.integer' => 'رقم الدورة غير صحيح',
            'round_id.exists' => 'الدورة غير موجودة',
            'threshold.required' => 'يجب تحديد النسبة',
            'threshold.numeric' => 'النسبة يجب أن تكون رقم',
            'threshold.min' => 'النسبة يجب أن تكون بين 0 و 100',
            'threshold.max' => 'النسبة يجب أن تكون بين 0 و 100',
            'message.required' => 'نص الرسالة مطلوب',
            'message.string' => 'نص الرسالة يجب أن يكون نص',
            'message.max' => 'نص الرسالة طويل جداً',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first(),
        ], 422));
    }

    public function authorize(): bool
    {
        return true;
    }
}
