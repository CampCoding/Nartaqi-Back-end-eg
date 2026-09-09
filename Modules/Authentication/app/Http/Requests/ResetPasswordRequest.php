<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'required|string|min:8|max:18',
            'code' => 'required|digits:6',
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}


