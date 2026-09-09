<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ActiveAndInactivRoundRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required|exists:rounds,id',
            'active' => 'required|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'الجولة مطلوبة',
            'id.exists' => 'الجولة غير موجودة',
            'active.required' => 'حقل الحالة مطلوب',
            'active.integer' => 'حقل الحالة يجب أن يكون صحيح أو خطأ',
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

    public function authorize(): bool
    {
        return true;
    }
}
