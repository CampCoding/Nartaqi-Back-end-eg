<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionOptionsRequest extends FormRequest
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
            'id' => 'required|exists:question_bank_options,id',
            'option_text' => 'required|string',
            'is_correct' => 'required|boolean',
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
            'id.required' => 'معرف الخيار مطلوب',
            'id.exists' => 'الخيار غير موجود',
            'option_text.required' => 'نص الخيار مطلوب',
            'option_text.string' => 'نص الخيار يجب أن يكون نص',
            'is_correct.required' => 'حالة صحة الخيار مطلوبة',
            'is_correct.boolean' => 'حالة صحة الخيار يجب أن تكون صحيح أو خطأ',
        ];
    }
}
