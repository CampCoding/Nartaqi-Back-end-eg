<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

 use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class StudentSignUp extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|unique:students,phone',
            'gender' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب',
            'password.required' => 'يجب كتابه كلمه السر',
            'password.min' =>  'كلمة السر يجب ان تكون 8 احرف',
            'phone.required' => 'يجب كتابه رقم الهاتف',
            'gender.required' => 'يجب اختيار الجنس',
            'phone.unique' => 'رقم الهاتف مستخدم من قبل',
        ];
    }
    /**
     * Determine if the user is authorized to make this request.
     */


    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'message' => 'خطأ في إدخال البيانات',
                'errors' => $validator->errors(), // 👈 this includes all the custom messages
            ], 422)
        );
    }

    public function authorize(): bool
    {
        return true;
    }
}
