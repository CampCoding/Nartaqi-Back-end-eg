<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EditExamSectionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|exists:exam_sections,id',
            'title' => 'required|string',
            'description' => 'nullable',
            'time_if_free' => 'sometimes|date_format:H:i:s',
            'success_percentage' => 'sometimes',
        ];
    }
    public function messages(): array
    {
        return [
            'id.required' => 'معرف القسم مطلوب',
            'id.exists' => 'القسم غير موجود',
            'title.required' => 'العنوان مطلوب',
            'title.string' => 'العنوان يجب أن يكون نص',
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
    public function authorize(): bool
    {
        return true;
    }
}
