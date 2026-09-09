<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddParagraphQuestionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'paragraph_id' => 'required|exists:question_paragraphs,id',
            'exam_section_id' => 'required|exists:exam_sections,id',
            'question_text' => 'required|string',
            'instructions' => 'nullable',
            'mcq_array' => 'required|array|min:1',
            'mcq_array.*.answer' => 'required|string',
            'mcq_array.*.correct_or_not' => 'required|in:0,1',
            'mcq_array.*.question_explanation' => 'nullable',
            'description' => 'nullable',

        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'paragraph_id.required' => 'الفقرة مطلوبة',
            'paragraph_id.exists' => 'الفقرة غير موجودة',
            'exam_section_id.required' => 'القسم مطلوب',
            'exam_section_id.exists' => 'القسم غير موجود',
            'question_text.required' => 'السؤال مطلوب',
            'question_text.string' => 'السؤال يجب أن يكون نص',
            'instructions.required' => 'التعليمات مطلوبة',
            'instructions.string' => 'التعليمات يجب أن تكون نص',
            'mcq_array.required' => 'الإجابات مطلوبة',
            'mcq_array.array' => 'الإجابات يجب أن تكون مصفوفة',
            'mcq_array.min' => 'يجب إضافة إجابة واحدة على الأقل',
            'mcq_array.*.answer.required' => 'نص الإجابة مطلوب',
            'mcq_array.*.answer.string' => 'نص الإجابة يجب أن يكون نص',
            'mcq_array.*.correct_or_not.required' => 'يجب تحديد الإجابة الصحيحة',
            'mcq_array.*.correct_or_not.in' => 'قيمة الإجابة الصحيحة يجب أن تكون 0 أو 1',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
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
