<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGeneralRatingRequest extends FormRequest
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
            'question' => 'required|string|max:500',
            'answers' => 'required|array|min:2',
            'answers.*' => 'required|string|max:255',
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
            'question.required' => 'السؤال مطلوب',
            'question.string' => 'السؤال يجب أن يكون نص',
            'question.max' => 'السؤال يجب ألا يتجاوز 500 حرف',
            'answers.required' => 'الإجابات مطلوبة',
            'answers.array' => 'الإجابات يجب أن تكون مصفوفة',
            'answers.min' => 'يجب إضافة إجابتين على الأقل',
            'answers.*.required' => 'كل إجابة مطلوبة',
            'answers.*.string' => 'كل إجابة يجب أن تكون نص',
            'answers.*.max' => 'كل إجابة يجب ألا تتجاوز 255 حرف',
        ];
    }
}
