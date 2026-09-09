<?php

namespace Modules\Blogs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BlogCommentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'blog_id' => 'required|exists:blogs,id',
            'comment' => 'required|string',
            'rating' => 'sometimes|integer|min:1|max:5',
            'hidden' => 'boolean|default:1',
        ];
    }

    public function messages(): array
    {
        return [
            'blog_id.required' => 'معرف المدونة مطلوب.',
            'blog_id.exists' => 'معرف المدونة غير صالح.',
            'comment.required' => 'التعليق مطلوب.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first(),
        ], 422));
    }

        public function wantsJson(): bool
    {
        return true;
    }

    /**
     * Determine if the student is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
