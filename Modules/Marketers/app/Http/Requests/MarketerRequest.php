<?php

namespace Modules\Marketers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MarketerRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:marketers,email',
            'national_id' => 'required|string|size:10|unique:marketers,national_id',
            'city' => 'required|string|max:100',
            'description' => 'required|string',
            'cv' => 'required|file',
            'account_owner' => 'required|string|max:255',
            'account_number' => 'required|string|unique:marketers,account_number',
            'iban_number' => 'required|string|unique:marketers,iban_number',
            'whatsapp_number' => 'required|string|max:15',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل.',
            'national_id.required' => 'الرقم القومي مطلوب.',
            'national_id.unique' => 'الرقم الوطني مستخدم بالفعل.',
            'city.required' => 'المدينة مطلوبة.',
            'description.required' => 'النبذة مطلوبة.',
            'cv.required' => 'السيرة الذاتية مطلوبة.',
            'account_owner.required' => 'اسم صاحب الحساب مطلوب.',
            'account_number.required' => 'رقم الحساب مطلوب.',
            'account_number.unique' => 'رقم الحساب مستخدم بالفعل.',
            'iban_number.required' => 'رقم الآيبان مطلوب.',
            'iban_number.unique' => 'رقم الآيبان مستخدم بالفعل.',
            'whatsapp_number.required' => 'رقم الواتساب مطلوب.',
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
