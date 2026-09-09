<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeleteCourseRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|exists:course_categories,id',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */

    public function messages(): array
    {
        return [
            'id.required' => ' التصنيف مطلوب',
            'id.exists' => ' التصنيف غير موجود',
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

    /** Ensure this request always expects JSON. */
    public function wantsJson(): bool
    {
        return true;
    }

    public function authorize(): bool
    {
        return true;
    }
}
