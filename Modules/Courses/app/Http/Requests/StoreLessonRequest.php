<?php

namespace Modules\Courses\Http\Requests;

use Dotenv\Validator;
use Illuminate\Contracts\Validation\Validator as ValidationValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreLessonRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    protected $table = 'lessons';
    public $timestamps = false;
    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'round_content_id' => 'required|exists:round_contents,id',
            'description' => 'nullable',
            'show_date' => 'nullable',
            'type' => 'required|string|max:255',
        ];
    }
    public function messages(): array
    {
        return [
            'title.required' => 'العنوان مطلوب',
            'title.string' => 'العنوان يجب أن يكون نص',
            'type.required' => 'النوع مطلوب',
            'type.string' => 'النوع يجب أن يكون نص',
        ];
    }
    protected function failedValidation(ValidationValidator $validator): void
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
