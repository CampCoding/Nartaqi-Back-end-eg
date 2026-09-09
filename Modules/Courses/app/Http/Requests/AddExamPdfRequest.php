<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddExamPdfRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'lesson_id' => 'required',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable',
            // Accept an uploaded PDF file; we will store its path in "pdf_url"
            'pdf_url' => 'required|file|mimes:pdf|max:20480', // max 20MB
            'type' => 'nullable',
            'for_type' => 'required|string'
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
