<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddQuestionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    protected $table = 'questions';
    public $timestamps = false;
    public function rules(): array
    {
        $rules = [
            'exam_section_id' => 'required|exists:exam_sections,id',
            'question_type' => 'required|in:essay,mcq,paragraph_mcq,t_f',
            'description' => 'nullable'


        ];

        // For paragraph_mcq, we need paragraph_content and questions array
        if ($this->question_type === 'paragraph_mcq') {
            $rules['paragraph_content'] = 'nullable';
            $rules['voice'] = 'nullable';
            $rules['questions'] = 'required|array|min:1';
            $rules['questions.*.question_text'] = 'required|string';
            $rules['questions.*.instructions'] = 'nullable';
            $rules['questions.*.mcq_array'] = 'required|array|min:1';
            $rules['questions.*.mcq_array.*.answer'] = 'required|string';
            $rules['questions.*.mcq_array.*.question_explanation'] = 'nullable|string';
            $rules['questions.*.mcq_array.*.correct_or_not'] = 'required|in:0,1';
        } else {
            // For essay and mcq, use the old structure
            $rules['question_text'] = 'required|string';
            $rules['instructions'] = 'nullable';

            if ($this->question_type === 'essay') {
                $rules['answer_text'] = 'required|string';
            } elseif ($this->question_type === 'mcq' || $this->question_type === 't_f') {
                $rules['mcq_array'] = 'required|array|min:1';
                $rules['mcq_array.*.answer'] = 'required|string';
                $rules['mcq_array.*.correct_or_not'] = 'required|in:0,1';
                $rules['mcq_array.*.question_explanation'] = 'nullable|string';
            }
        }

        return $rules;
    }
    public function messages(): array
    {
        return [
            'exam_section_id.required' => 'معرف القسم الإمتحاني مطلوب',
            'exam_section_id.exists' => 'القسم الإمتحاني غير موجود',
            'question_text.required' => 'السؤال مطلوب',
            'question_text.string' => 'السؤال يجب أن يكون نص',
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
