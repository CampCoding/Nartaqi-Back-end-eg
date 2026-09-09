<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreRoundLiveRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'lesson_id' => 'required|exists:lessons,id',
            'title' => 'required|string|max:255',
            'link' => 'nullable',
            'time' => 'nullable',
            'date' => 'nullable',
            'active' => 'required|integer|in:0,1',
            'password' => 'nullable',
            'meeting_id' => 'nullable',
            'end_time' => 'nullable',

        ];
    }
    public function messages(): array
    {
        return [
            'lesson_id.required' => 'الدرس مطلوب',
            'lesson_id.exists' => 'الدرس غير موجود',
            'title.required' => 'العنوان مطلوب',
            'title.string' => 'العنوان يجب أن يكون نص',
            'title.max' => 'العنوان لا يجب أن يتجاوز 255 حرف',

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
