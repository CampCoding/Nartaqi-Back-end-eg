<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EditCourseRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|exists:course_categories,id',
            'name' => 'required|string|max:255|unique:course_categories,name,' . $this->id,
            'description' => 'nullable'
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'id.required' => ' التصنيف مطلوب',
            'id.exists' => ' التصنيف غير موجود',
            'name.required' => 'اسم الدوره مطلوب',
            'name.string' => 'اسم الدوره يجب أن يكون نص',
            'name.max' => 'اسم الدوره لا يجب أن يتجاوز 255 حرف',
            'name.unique' => 'هذا الاسم موجود بالفعل',
        ];
    }

    /**
     * Force JSON response on validation failure (avoid HTML redirect page).
     */
    protected function failedValidation(Validator $validator): void
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
