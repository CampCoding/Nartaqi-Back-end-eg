<?php

namespace Modules\Certificates\Http\Requests;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AdminCertificatesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:students,id',
            'round_id' => 'required|exists:rounds,id',
            'application_id' => 'required|exists:certificate_applications,id',
            'certification_name' => 'required|string',
            'pdf_path' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'معرف الطالب مطلوب',
            'student_id.exists' => 'الطالب غير موجود',
            'round_id.required' => 'معرف الدورة مطلوب',
            'round_id.exists' => 'الدورة غير موجودة',
            'application_id.required' => 'معرف الشهادة مطلوب',
            'application_id.exists' => 'الشهادة غير موجودة',
            'certification_name.required' => 'اسم الشهادة مطلوب',
            'pdf_path.required' => 'مسار ملف الشهادة مطلوب',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first(),
        ], 422));
    }

        public function wantsJson(): bool
    {
        return true;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


}
