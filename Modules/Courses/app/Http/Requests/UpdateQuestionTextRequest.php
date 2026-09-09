<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionTextRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|exists:questions_bank,id',
            'question_text' => 'sometimes|string',
            'question_type' => 'sometimes|string|in:mcq,true_false,short_answer',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'id.required' => 'معرف السؤال مطلوب',
            'id.exists' => 'السؤال غير موجود',
            'question_text.string' => 'نص السؤال يجب أن يكون نص',
            'question_type.string' => 'نوع السؤال يجب أن يكون نص',
            'question_type.in' => 'نوع السؤال يجب أن يكون: mcq, true_false, أو short_answer',
        ];
    }
}
