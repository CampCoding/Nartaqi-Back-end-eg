<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionBankRequest extends FormRequest
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
            'question_type' => 'sometimes|string',
            'instructions' => 'nullable|string',
            'question_bank_skills_id' => 'sometimes|integer',
            'options' => 'sometimes|array|min:2',
            'options.*.id' => 'sometimes|exists:question_bank_options,id',
            'options.*.option_text' => 'required_with:options|string',
            'options.*.is_correct' => 'required_with:options|boolean',
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
            'question_bank_skills_id.integer' => 'معرف المهارة يجب أن يكون رقم',
            'options.array' => 'الخيارات يجب أن تكون مصفوفة',
            'options.min' => 'يجب إضافة خيارين على الأقل',
            'options.*.id.exists' => 'الخيار غير موجود',
            'options.*.option_text.required_with' => 'نص الخيار مطلوب',
            'options.*.option_text.string' => 'نص الخيار يجب أن يكون نص',
            'options.*.is_correct.required_with' => 'حالة صحة الخيار مطلوبة',
            'options.*.is_correct.boolean' => 'حالة صحة الخيار يجب أن تكون صحيح أو خطأ',
        ];
    }
}
