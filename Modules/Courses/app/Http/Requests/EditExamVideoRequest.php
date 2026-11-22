<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EditExamVideoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'required|string',
            // Optional: only changes the lock when sent.
            'is_locked' => 'nullable|boolean'
        ];
    }
    public function messages(): array
    {
        return [
            'id.required' => 'معرف الفيديو مطلوب',
            'id.exists' => 'الفيديو غير موجود',
            'title.nullable' => 'العنوان مطلوب',
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
