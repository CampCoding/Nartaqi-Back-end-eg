<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EditRoundLiveRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|exists:round_lives,id',
            'title' => 'sometimes|string|max:255',
            'link' => 'sometimes|string|max:255',
            'time' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'password' => 'nullable',
            'meeting_id' => 'nullable',
            'end_time' => 'sometimes|string|max:255',
        ];
    }
    public function messages(): array
    {
        return [
            'id.required' => 'المحتوى مطلوب',
            'id.exists' => 'المحتوى غير موجود',
            'title.string' => 'العنوان يجب أن يكون نص',
            'title.max' => 'العنوان لا يجب أن يتجاوز 255 حرف',
            'link.string' => 'الرابط يجب أن يكون نص',
            'link.max' => 'الرابط لا يجب أن يتجاوز 255 حرف',
            'time.string' => 'الوقت يجب أن يكون نص',
            'time.max' => 'الوقت لا يجب أن يتجاوز 255 حرف',
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
