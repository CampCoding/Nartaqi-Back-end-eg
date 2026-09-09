<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreRoundContentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'round_id' => 'required|exists:rounds,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable',
            'type' => 'required|string|in:basic,lecture',
            'show_date' => 'nullable',
        ];
    }
    public function messages(): array
    {
        return [
            'round_id.required' => 'الجولة مطلوبة',
            'round_id.exists' => 'الجولة غير موجودة',
            'title.required' => 'العنوان مطلوب',
            'title.string' => 'العنوان يجب أن يكون نص',
            'title.max' => 'العنوان لا يجب أن يتجاوز 255 حرف',
            'type.required' => 'النوع مطلوب',
            'type.string' => 'النوع يجب أن يكون نص',
            'type.in' => 'النوع يجب أن يكون basic أو lecture',
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
