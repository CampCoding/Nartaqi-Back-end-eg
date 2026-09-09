<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreStudentAnswerRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:students,id',
            'exam_id' => 'required|exists:exams,id',
            'answers' => 'nullable|array',
            'answers.*.question_id' => 'nullable|exists:questions,id',
            'answers.*.type' => 'nullable|string|in:mcq,paragraph,t_f',
            'answers.*.student_answer' => 'nullable',
            'answers.*.correct_answer' => 'nullable',
            'answers.*.is_correct' => 'nullable|boolean',
        ];
    }
    public function messages(): array
    {

        return [
            'student_id.required' => 'الطالب مطلوب',
            'student_id.exists' => 'الطالب غير موجود',
            'exam_id.required' => 'الامتحان مطلوب',
            'exam_id.exists' => 'الامتحان غير موجود'
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
