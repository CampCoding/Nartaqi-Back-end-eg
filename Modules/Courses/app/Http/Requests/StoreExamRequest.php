<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreExamRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [

            'title' => 'required|string|max:255',
            'description' => 'nullable',
            'free' => 'required|string|max:255',
            'time' => 'sometimes',
            'date' => 'nullable',
            'level' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'success_percentage' => 'nullable|numeric|min:0|max:100',
            'exam_label_id' => 'nullable'


        ];
    }
    public function messages(): array
    {
        return [
            'title.required' => 'العنوان مطلوب',
            'title.string' => 'العنوان يجب أن يكون نص',
            'title.max' => 'العنوان لا يجب أن يتجاوز 255 حرف',

            'time.sometimes' => 'الوقت مطلوب',
            'type.required' => 'النوع مطلوب',
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
