<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeleteRoundRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required|exists:rounds,id',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => ' الجولة مطلوب',
            'id.exists' => 'الجولة غير موجودة',
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
