<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddExamVideoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'lesson_id' => 'required',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'required|string',
            'for_type' => 'required|string'

        ];
    }
    public function messages(): array
    {
        return [
            'lesson_id.required' => 'الدرس مطلوب',
            'lesson_id.exists' => 'الدرس غير موجود',
            'title.nullable' => 'العنوان مطلوب',
            'title.string' => 'العنوان يجب أن يكون نص',
            'title.max' => 'العنوان لا يجب أن يتجاوز 255 حرف',
            'video_url.required' => 'رابط الفيديو مطلوب',
            'video_url.string' => 'رابط الفيديو يجب أن يكون نص',
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
