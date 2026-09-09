<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserRoundsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:students,id',
            'round_id' => 'required|exists:rounds,id',

        ];
    }

    public function messages(): array
    {
        return [
            'round_id.required' => 'معرف الدوره مطلوبه',
            'round_id.exists' => 'الدوره غير موجوده',
            'student_id.required' => 'معرف الطالب مطلوب',
            'student_id.exists' => 'الطالب غير موجود',
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
