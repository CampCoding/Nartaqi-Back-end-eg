<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditStudentAchievementResultsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|exists:student_achievement_results,id',
            'category_part_id' => 'sometimes|exists:category_parts,id',
            'title' => 'sometimes|string|max:255',
            'image' => [
                'sometimes',
                function ($attribute, $value, $fail) {
                    if ($value instanceof \Illuminate\Http\UploadedFile) {
                        $allowedExtensions = ['jpeg', 'png', 'jpg', 'gif', 'svg', 'webp'];
                        if (!in_array(strtolower($value->getClientOriginalExtension()), $allowedExtensions)) {
                            $fail('الملف يجب أن يكون صورة');
                        }
                    }
                },
            ],
            'video_link' => 'nullable',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'id.required' => 'المعرف مطلوب',
            'id.exists' => 'السجل المحدد غير موجود',
            'category_part_id.exists' => 'القسم المحدد غير موجود',
            'title.required' => 'العنوان مطلوب',
            'title.string' => 'العنوان يجب أن يكون نص',
            'title.max' => 'العنوان يجب ألا يتجاوز 255 حرف',
            'image.required' => 'الصورة مطلوبة'
        ];
    }
}
