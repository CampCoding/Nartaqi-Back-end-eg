<?php

namespace Modules\Blogs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BlogRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'content' => 'required|string',
            'image' => 'nullable',
            'published_at' => 'nullable|date',
            'related_blogs_ids' => 'nullable',
            'hidden' => 'boolean|default:1',
            'views' => 'integer|default:0',
        ];
    }


    public function messages(): array
    {
        return [
            'title.required' => 'العنوان مطلوب.',
            'content.required' => 'المحتوى مطلوب.',
            'published_at.date' => 'يجب أن يكون تاريخًا صالحًا.',
            'hidden.boolean' => 'يجب أن يكون الحقل مخفيًا أو ظاهرًا.',

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
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
