<?php

namespace Modules\Team\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TeamRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'category' => 'required|in:administrators,technicians,technical_support,data_entry',
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'role' => 'required|string|max:100',
            'image' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'الفئة مطلوبة',
            'category.in' => 'الفئة غير صالحة',
            'name.required' => 'الاسم مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة',
            'role.required' => 'الدور مطلوب'
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
