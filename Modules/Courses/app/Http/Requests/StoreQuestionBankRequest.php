<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionBankRequest extends FormRequest
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
            'question_bank_skills_id' => 'required|integer',
            'type' => 'required|in:mcq,paragraph',
            'question_type' => 'required|string',

            // MCQ type at root level
            'question_text' => 'required_if:type,mcq|string',
            'mcq_array' => 'required_if:type,mcq|array',
            'mcq_array.*.answer' => 'required_if:type,mcq|string',
            'mcq_array.*.is_correct' => 'required_if:type,mcq',

            // Paragraph type
            'questions' => 'required_if:type,paragraph|array',
            'questions.*.question_text' => 'required_if:type,paragraph|string',
            'questions.*.instructions' => 'nullable|string',
            'questions.*.mcq_array' => 'required_if:type,paragraph|array|min:1',
            'questions.*.mcq_array.*.answer' => 'required_if:type,paragraph|string',
            'questions.*.mcq_array.*.is_correct' => 'required_if:type,paragraph',
            'instructions' => 'nullable|string',
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
            'question_text.required_if' => 'نص السؤال مطلوب',
            'question_bank_skills_id.required' => 'معرف المهارة مطلوب',
            'question_type.required' => 'نوع السؤال مطلوب (كمي/لفظي)',
            'type.required' => 'نوع السؤال مطلوب (mcq/paragraph)',
            'questions.required_if' => 'الأسئلة مطلوبة للفقرة',
        ];
    }
}
