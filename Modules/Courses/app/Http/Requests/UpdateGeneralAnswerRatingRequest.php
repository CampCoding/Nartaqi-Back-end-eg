<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralAnswerRatingRequest extends FormRequest
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
            'id' => 'required|exists:general_answers_ratings,id',
            'answer' => 'required|string|max:255',
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
            'id.required' => 'معرف الإجابة مطلوب',
            'id.exists' => 'الإجابة غير موجودة',
            'answer.required' => 'الإجابة مطلوبة',
            'answer.string' => 'الإجابة يجب أن تكون نص',
            'answer.max' => 'الإجابة يجب ألا تتجاوز 255 حرف',
        ];
    }
}
