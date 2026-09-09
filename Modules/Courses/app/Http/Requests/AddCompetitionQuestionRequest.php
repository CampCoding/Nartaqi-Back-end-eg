<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddCompetitionQuestionRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'competition_id' => 'required|integer|exists:competitions,id',
            'question_text' => 'required|string',
            'question_type' => 'required|string',
            'options' => 'required|array|min:2',
            'options.*.option_text' => 'required|string',
            'options.*.is_correct' => 'required|boolean'
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // competition_id messages
            'competition_id.required' => 'معرف المسابقة مطلوب',
            'competition_id.integer' => 'معرف المسابقة يجب أن يكون رقم صحيح',
            'competition_id.exists' => 'المسابقة المحددة غير موجودة في النظام',

            // question_text messages
            'question_text.required' => 'نص السؤال مطلوب',
            'question_text.string' => 'نص السؤال يجب أن يكون نص',

            // question_type messages
            'question_type.required' => 'نوع السؤال مطلوب',
            'question_type.string' => 'نوع السؤال يجب أن يكون نص',

            // options messages
            'options.required' => 'خيارات السؤال مطلوبة',
            'options.array' => 'خيارات السؤال يجب أن تكون مصفوفة',
            'options.min' => 'يجب إضافة خيارين على الأقل للسؤال',

            // options.*.option_text messages
            'options.*.option_text.required' => 'نص الخيار مطلوب لجميع الخيارات',
            'options.*.option_text.string' => 'نص الخيار يجب أن يكون نص',

            // options.*.is_correct messages
            'options.*.is_correct.required' => 'يجب تحديد ما إذا كان الخيار صحيح أم لا',
            'options.*.is_correct.boolean' => 'قيمة is_correct يجب أن تكون true أو false'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'competition_id' => 'معرف المسابقة',
            'question_text' => 'نص السؤال',
            'question_type' => 'نوع السؤال',
            'options' => 'الخيارات',
            'options.*.option_text' => 'نص الخيار',
            'options.*.is_correct' => 'صحة الخيار'
        ];
    }
}
