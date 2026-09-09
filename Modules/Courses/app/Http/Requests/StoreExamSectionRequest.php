<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreExamSectionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'exam_id' => 'required|exists:exams,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable',
            'time_if_free' => 'sometimes|date_format:H:i:s',
            'type' => 'required|in:intern,mock',
        ];
    }
    public function messages(): array
    {
        return [
            'exam_id.required' => 'معرف الامتحان مطلوب',
            'exam_id.exists' => 'الامتان غير موجود',
            'title.required' => 'العنوان مطلوب',
            'title.string' => 'العنوان يجب أن يكون نص',
            'title.max' => 'العنوان لا يجب أن يتجاوز 255 حرف',

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
