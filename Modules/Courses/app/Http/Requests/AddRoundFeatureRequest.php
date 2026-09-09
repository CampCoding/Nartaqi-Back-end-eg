<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddRoundFeatureRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'round_id' => 'required|exists:rounds,id',
            'title' => 'required|string',
            'description' => 'nullable',
            'image' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'round_id.required' => 'معرف الجولة مطلوب',
            'round_id.exists' => 'الجولة غير موجودة',
            'title.required' => 'العنوان مطلوب',

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
