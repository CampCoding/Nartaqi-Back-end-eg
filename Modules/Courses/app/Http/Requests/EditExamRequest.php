<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EditExamRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|exists:exams,id',
            'title' => 'required|string',
            'description' => 'nullable',
            'exam_type' => 'sometimes|string|max:255',
            'lesson_id' => 'sometimes',
            'free' => 'required|string|max:255',
            'time' => 'sometimes',
            'date' => 'nullable',
            'type' => 'sometimes|string|max:255',
            'success_percentage' => 'nullable|numeric|min:0|max:100',
        ];
    }
    public function messages(): array
    {
        return [
            'id.required' => 'معرف الامتحان مطلوب',
            'id.exists' => 'الامتان غير موجود',
            'title.required' => 'العنوان مطلوب',
            'title.string' => 'العنوان يجب أن يكون نص',
            'exam_type.required' => 'نوع الامتحان مطلوب',
            'exam_type.string' => 'نوع الامتحان يجب أن يكون نص',
            'type.sometimes' => 'النوع مطلوب',
            'type.string' => 'النوع يجب أن يكون نص',
            'type.max' => 'النوع لا يجب أن يتجاوز 255 حرف',
            'success_percentage.numeric' => 'نسبة النجاح يجب أن تكون رقماً',
            'success_percentage.min' => 'نسبة النجاح يجب أن تكون أكبر من أو تساوي 0',
            'success_percentage.max' => 'نسبة النجاح يجب أن تكون أقل من أو تساوي 100',
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
