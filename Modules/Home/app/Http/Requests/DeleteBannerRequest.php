<?php

namespace Modules\Home\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeleteBannerRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:home_banners,id',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'حقل المعرف مطلوب.',
            'id.integer' => 'المعرف يجب أن يكون رقماً صحيحاً.',
            'id.exists' => 'البانر المحدد غير موجود.',
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
