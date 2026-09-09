<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCompetitionRequest extends FormRequest
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
            'id' => 'required|exists:competitions,id',
            'competition_name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|max:100',
            'idea' => 'sometimes|string',
            'prize' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'active' => 'sometimes|boolean',
            'image' => 'sometimes|image|mimes:jpeg,jpg,png,gif|max:2048'
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
            'id.required' => 'معرف المسابقة مطلوب',
            'id.exists' => 'المسابقة غير موجودة',
            'competition_name.string' => 'اسم المسابقة يجب أن يكون نص',
            'competition_name.max' => 'اسم المسابقة يجب ألا يتجاوز 255 حرف',
            'type.string' => 'نوع المسابقة يجب أن يكون نص',
            'type.max' => 'نوع المسابقة يجب ألا يتجاوز 100 حرف',
            'idea.string' => 'فكرة المسابقة يجب أن تكون نص',
            'prize.string' => 'الجائزة يجب أن تكون نص',
            'prize.max' => 'الجائزة يجب ألا تتجاوز 255 حرف',
            'start_date.date' => 'تاريخ البداية يجب أن يكون تاريخ صحيح',
            'end_date.date' => 'تاريخ النهاية يجب أن يكون تاريخ صحيح',
            'end_date.after_or_equal' => 'تاريخ النهاية يجب أن يكون مساوياً أو بعد تاريخ البداية',
            'active.boolean' => 'حقل التفعيل يجب أن يكون صحيح أو خطأ',
            'image.image' => 'يجب أن يكون الملف صورة',
            'image.mimes' => 'الصورة يجب أن تكون من نوع: jpeg, jpg, png, gif',
            'image.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
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
}
