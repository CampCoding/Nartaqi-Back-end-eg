<?php

namespace Modules\Certificates\Http\Requests;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CertificateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'round_id' => 'required|exists:rounds,id',
            'name' => 'required|string|max:255',
            'national_id' => 'required|string|max:20',
            'nationality' => 'required|string|max:100',
            'phone' => 'required|string|max:15',
            'email' => 'required|email|max:255',
            'status' => 'in:pending,approved,rejected',
        ];
    }

        public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب',
            'name.string' => 'الاسم يجب ان يكون نص',
            'national_id.required' => 'الرقم القومي مطلوب',
            'national_id.max' => 'الرقم القومي يجب ان لا يزيد عن 20 حرف',
            'national_id.unique' => 'الرقم القومي مستخدم من قبل',
            'nationality.required' => 'الجنسية مطلوبة',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.max' => 'رقم الهاتف يجب ان لا يزيد عن 15 حرف',
            'phone.unique' => 'رقم الهاتف مستخدم من قبل',
            'email.required' => 'البريد الالكتروني مطلوب',
            'email.email' => 'البريد الالكتروني يجب ان يكون بريد صالح',
            'email.unique' => 'البريد الالكتروني مستخدم من قبل',

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
