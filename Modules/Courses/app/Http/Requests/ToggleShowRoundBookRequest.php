<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToggleShowRoundBookRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'round_id' => 'required|exists:rounds,id',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'round_id.required' => 'معرف الدورة مطلوب',
            'round_id.exists'   => 'الدورة غير موجودة',
        ];
    }
}
