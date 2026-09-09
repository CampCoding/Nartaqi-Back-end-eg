<?php

namespace Modules\Faqs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class FaqRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'category' => 'required|in:all,general,professional_license,support,courses,enroll',
            'question' => 'required|string',
            'answer' => 'required|string',
            'hidden' => 'sometimes|boolean',
            'type' => 'required|string'
        ];
    }
    public function messages(): array
    {
        return [
            'category.required' => 'حقل التصنيف مطلوب.',
            'category.in' => 'التصنيف المحدد غير صالح.',
            'question.required' => 'حقل السؤال مطلوب.',
            'answer.required' => 'حقل الإجابة مطلوب.',
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
