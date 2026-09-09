<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EditExamPdfRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|exists:exam_pdfs,id',
            'lesson_id' => 'sometimes',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable',
            'pdf_url' => 'nullable|file|mimes:pdf|max:20480',
            'type' => 'nullable',
        ];
    }
    public function messages(): array
    {
        return [
            'id.required' => 'معرف الملف المراد تعديله مطلوب',
            'id.exists' => 'الملف المراد تعديله غير موجود',
            'title.nullable' => 'العنوان مطلوب',
            'pdf_url.nullable' => 'رابط الملف مطلوب',
            'pdf_url.file' => 'رابط الملف يجب أن يكون ملف',
            'pdf_url.mimes' => 'رابط الملف يجب أن يكون من نوع PDF',
            'pdf_url.max' => 'رابط الملف يجب أن يكون أقل من 20 ميجابايت',
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
