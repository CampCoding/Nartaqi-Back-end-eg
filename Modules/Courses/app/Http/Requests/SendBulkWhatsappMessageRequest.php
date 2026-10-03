<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendBulkWhatsappMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'message' => 'required|string',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:51200',
        ];
    }

    public function messages(): array
    {
        return [
            'student_ids.required' => 'يجب اختيار الطلاب',
            'student_ids.array' => 'الطلاب يجب أن تكون قائمة',
            'student_ids.min' => 'يجب اختيار طالب واحد على الأقل',
            'student_ids.*.exists' => 'أحد الطلاب المحددين غير موجود',
            'message.required' => 'نص الرسالة مطلوب',
            'file.mimes' => 'الملف يجب أن يكون صورة (jpg, jpeg, png) أو ملف PDF',
            'file.max' => 'حجم الملف لا يجب أن يتجاوز 50 ميجابايت',
            'file.uploaded' => 'فشل رفع الملف، غالباً حجمه أكبر من الحد المسموح على السيرفر',
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

    public function authorize(): bool
    {
        return true;
    }
}
