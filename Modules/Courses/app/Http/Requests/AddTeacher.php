<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class AddTeacher extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:teachers,email',
            'gender' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'required|string',
            'facebook' => 'nullable|string|max:255',
            'twitter' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'linkedin' => 'nullable|string|max:255',
            'youtube' => 'nullable|string|max:255',
            'tiktok' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب',
            'name.string' => 'الاسم يجب أن يكون نص',
            'name.max' => 'الاسم لا يجب أن يتجاوز 255 حرف',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني يجب أن يكون صالح',
            'email.unique' => 'البريد الإلكتروني مستخدم من قبل',
            'gender.required' => 'الجنس مطلوب',
            'gender.string' => 'الجنس يجب أن يكون نص',
            'gender.max' => 'الجنس لا يجب أن يتجاوز 255 حرف',
            'image.required' => 'الصورة مطلوبة',
            'image.image' => 'الصورة يجب أن يكون نص',
            'image.mimes' => 'الصورة يجب أن يكون نص',
            'image.max' => 'الصورة يجب أن يكون نص',
            'description.required' => 'الوصف مطلوب',
            'description.string' => 'الوصف يجب أن يكون نص',
            'facebook.string' => 'الفيسبوك يجب أن يكون نص',
            'facebook.max' => 'الفيسبوك لا يجب أن يتجاوز 255 حرف',
            'twitter.string' => 'التويتر يجب أن يكون نص',
            'twitter.max' => 'التويتر لا يجب أن يتجاوز 255 حرف',
            'instagram.string' => 'الانستغرام يجب أن يكون نص',
            'instagram.max' => 'الانستغرام لا يجب أن يتجاوز 255 حرف',
            'linkedin.string' => 'اللينكدين يجب أن يكون نص',
            'linkedin.max' => 'اللينكدين لا يجب أن يتجاوز 255 حرف',
            'youtube.string' => 'اليوتيوب يجب أن يكون نص',
            'youtube.max' => 'اليوتيوب لا يجب أن يتجاوز 255 حرف',
            'tiktok.string' => 'التيكتوك يجب أن يكون نص',
            'tiktok.max' => 'التيكتوك لا يجب أن يتجاوز 255 حرف',
            'website.string' => 'الموقع الإلكتروني يجب أن يكون نص',
            'website.max' => 'الموقع الإلكتروني لا يجب أن يتجاوز 255 حرف',
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
