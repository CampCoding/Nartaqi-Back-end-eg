<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator as ValidationValidator;
class DeleteRoundResources extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|exists:round_resources,id',
        ];
        }
    public function messages(): array
    {
        return [
            'id.required' => 'معرف الملف المراد حذفه مطلوب',
            'id.exists' => 'الملف المراد حذفه غير موجود',
        ];
    }
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => $validator->errors()->first(),
        ], 422));
    }
    public function authorize(): bool
    {
        return true;
    }
}
