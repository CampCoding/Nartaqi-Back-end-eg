<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateCourseCategoryRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:course_categories,name',
            'description' => 'nullable',
            'image' => 'nullable',
            'active' => 'required|integer|in:0,1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم التصنيف مطلوب',
            'name.string' => 'اسم التصنيف يجب أن يكون نص',
            'name.max' => 'اسم التصنيف لا يجب أن يتجاوز 255 حرف',
            'name.unique' => 'التصنيف موجود بالفعل',

            'active.integer' => 'حقل الحالة يجب أن يكون صحيح أو خطأ',
        ];
    }

    /**
     * Force JSON response on validation failure (avoid HTML redirect page).
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first(),
        ], 422));
    }

    /** Ensure this request always expects JSON. */
    public function wantsJson(): bool
    {
        return true;
    }
}
