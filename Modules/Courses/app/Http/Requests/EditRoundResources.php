<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator as ValidationValidator;
use App\Support\Uploads;

class EditRoundResources extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required|exists:round_resources,id',
            'title' => 'required|string',
            'description' => 'nullable',
            'file' => 'nullable|file|mimes:pdf|max:' . Uploads::PDF_MAX_KB, // PDF file, max 500MB (optional for edit)
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
            'file.file' => 'الملف يجب أن يكون ملف مرفوع',
            'file.max' => Uploads::PDF_MAX_MESSAGE,
            'file.uploaded' => Uploads::UPLOAD_FAILED_MESSAGE,
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
