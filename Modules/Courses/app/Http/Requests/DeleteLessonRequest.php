<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeleteLessonRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|exists:lessons,id',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */

    public function messages(): array
    {
        return [
            'id.required' => ' الدرس مطلوب',
            'id.exists' => ' الدرس غير موجود',
        ];
    }

    /**
     * Force JSON response on validation failure (avoid HTML redirect page).
     */
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
