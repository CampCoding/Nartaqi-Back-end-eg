<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator as ValidationValidator;
class GetRoundResources extends FormRequest
{
    public function rules(): array
    {
        return [
            'round_id' => 'required|exists:rounds,id',
        ];
    }

    public function messages(): array
    {
        return [
            'round_id.required' => 'معرف الجولة مطلوب',
            'round_id.exists' => 'الجولة غير موجودة',
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
