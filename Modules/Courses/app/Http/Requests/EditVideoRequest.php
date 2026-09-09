<?php

namespace Modules\Courses\Http\Requests;


use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EditVideoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|exists:videos,id',
            'title' => 'sometimes',
            'description' => 'nullable',
            'time' => 'nullable',
            'vimeo_link' => 'nullable|string',
            'youtube_link' => 'nullable|string',
            'free' => 'required|integer|in:0,1',
        ];
    }
    public function messages(): array
    {
        return [
            'lesson_id.required' => 'الدرس مطلوب',
            'lesson_id.exists' => 'الدرس غير موجود',
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
