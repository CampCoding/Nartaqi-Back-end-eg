<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EditRoundFeatureRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|exists:round_features,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable',
            'image' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'معرف الميزة مطلوب',
            'id.exists' => 'الميزة غير موجودة',

            'title.required' => 'العنوان مطلوب',
            'title.max' => 'العنوان يجب أن يكون أقل من 255 حرف'

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
