<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddVideoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'lesson_id' => 'required|exists:lessons,id',
            'title' => 'required|string',
            'description' => 'nullable',
            'vimeo_link' => 'nullable|string',
            'youtube_link' => 'nullable|string',
            'time' => 'nullable',
            'free' => 'required|integer|in:0,1',
        ];
    }
    public function messages(): array
    {
        return [
            'lesson_id.required' => 'الدرس مطلوب',
            'lesson_id.exists' => 'الدرس غير موجود',
            'vimeo_link.string' => 'رابط الفيديو يجب أن يكون نص',
            'youtube_link.string' => 'رابط الفيديو يجب أن يكون نص'
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
