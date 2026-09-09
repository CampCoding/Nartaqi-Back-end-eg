<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EditCategoryParts extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|exists:category_parts,id',
            'name' => 'required|string|max:255|unique:category_parts,name,' . $this->id . ',id,course_category_id,' . $this->course_category_id,
            'course_category_id' => 'required|exists:course_categories,id',
        ];
    }
    public function messages(): array
    {
        return [
            'id.required' => 'معرف التصنيف مطلوب',
            'id.exists' => 'التصنيف غير موجود',
            'name.required' => 'اسم التصنيف مطلوب',
            'name.string' => 'اسم التصنيف يجب أن يكون نص',
            'name.max' => 'اسم التصنيف لا يجب أن يتجاوز 255 حرف',
            'name.unique' => 'هذا الاسم موجود بالفعل في هذا التصنيف',
            'course_category_id.required' => 'معرف التصنيف مطلوب',
            'course_category_id.exists' => 'التصنيف غير موجود',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first(),
        ], 422));
    }
    public function authorize(): bool
    {
        return true;
    }
}
