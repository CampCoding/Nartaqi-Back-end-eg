<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreStudentGeneralRatingAnswersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:students,id',
            'answers' => 'required|array|min:1',
            'answers.*.general_rating_id' => 'required|exists:general_ratings,id',
            'answers.*.general_answer_rating_id' => 'required|exists:general_answers_ratings,id',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'الطالب مطلوب',
            'student_id.exists' => 'الطالب غير موجود',
            'answers.required' => 'الإجابات مطلوبة',
            'answers.array' => 'الإجابات يجب أن تكون في هيئة مصفوفة',
            'answers.min' => 'يجب إرسال إجابة واحدة على الأقل',
            'answers.*.general_rating_id.required' => 'السؤال مطلوب',
            'answers.*.general_rating_id.exists' => 'السؤال غير موجود',
            'answers.*.general_answer_rating_id.required' => 'الإجابة مطلوبة',
            'answers.*.general_answer_rating_id.exists' => 'الإجابة غير موجودة',
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first(),
        ], 422));
    }


    public function authorize(): bool
    {
        return true;
    }
}
