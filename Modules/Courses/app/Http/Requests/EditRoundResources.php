<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator as ValidationValidator;

class EditRoundResources extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required|exists:round_resources,id',
            'title' => 'required|string',
            'description' => 'nullable',
            'file' => 'nullable|mimes:pdf|max:10240', // PDF file, max 10MB (optional for edit)
            'show_date' => 'nullable'

        ];
    }
    public function messages(): array
    {
        return [
            'id.required' => 'معرف الملف المراد تعديله مطلوب',
            'id.exists' => 'الملف المراد تعديله غير موجود',
            'title.required' => 'العنوان مطلوب',
            'title.string' => 'العنوان يجب أن يكون نص',
            'file.mimes' => 'يجب أن يكون الملف من نوع PDF',
            'file.max' => 'حجم الملف يجب أن يكون أقل من 10 ميجابايت',
        ];
    }
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first(),
        ], 422));
    }
}
