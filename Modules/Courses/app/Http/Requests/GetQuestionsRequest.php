<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class GetQuestionsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'exam_section_id' => 'required|exists:exam_sections,id',
        ];
    }

    public function messages(): array
    {
        return [
            'exam_section_id.required' => 'معرف قسم الامتحان مطلوب',
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
