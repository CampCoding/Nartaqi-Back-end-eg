<?php

namespace Modules\Admins\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdminRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // For resource route Route::apiResource('admins', AdminsController::class), the parameter is 'admin' (which is the admin ID)
        $adminId = $this->route('admin') ?: $this->input('id');

        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:15|unique:admins,phone',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable',
            'role_id' => 'nullable|exists:roles,id',
            'permissions' => 'nullable|string',
        ];

        // If it's a PUT or PATCH request, or the id is passed in request body (e.g., POST to update), the password is optional, and we must ignore the current admin's email and phone
        if ($this->isMethod('put') || $this->isMethod('patch') || $adminId) {
            $rules['password'] = 'nullable|string|min:8|confirmed';
            $rules['phone'] = 'required|string|max:15|unique:admins,phone,' . $adminId;
            $rules['email'] = 'required|email|unique:admins,email,' . $adminId;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب.',
            'name.string' => 'يجب أن يكون الاسم نصًا.',
            'name.max' => 'يجب ألا يزيد الاسم عن 255 حرفًا.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.string' => 'يجب أن يكون رقم الهاتف نصًا.',
            'phone.max' => 'يجب ألا يزيد رقم الهاتف عن 15 حرفًا.',
            'phone.unique' => 'رقم الهاتف مستخدم بالفعل.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'يجب أن يكون البريد الإلكتروني صالحًا.',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'يجب أن تكون كلمة المرور 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first(),
        ], 422));
    }
}
