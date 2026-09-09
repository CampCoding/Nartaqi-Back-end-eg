<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EditRoundRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required|exists:rounds,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'price' => 'sometimes|numeric|min:0',
            'start_date' => 'nullable',
            'end_date' => 'nullable',
            'gender' => 'sometimes|in:male,female,both',
            'for' => 'sometimes|string|max:255',
            'goal' => 'required|string',
            'course_category_id' => 'sometimes|exists:course_categories,id',
            'category_part_id' => 'sometimes|exists:category_parts,id',
            'teacher_id' => 'nullable',
            'free' => 'sometimes|boolean',
            'capacity' => 'sometimes|integer|min:0',
            'time_show' => 'nullable',
            'round_road_map_book' => 'sometimes',
            'round_book' => 'sometimes',
            'source' => 'sometimes|integer|in:0,1',
            'round_type' => 'sometimes|in:course,exams_only',
            'have_certificate' => 'sometimes|integer|in:0,1',
            "category_part_free_id" => "nullable",
            "in_store" => "nullable",

        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => ' الجولة مطلوب',
            'id.exists' => 'الجولة غير موجودة',
            'name.string' => 'اسم الجولة يجب أن يكون نص',
            'name.max' => 'اسم الجولة لا يجب أن يتجاوز 255 حرف',
            'image.image' => 'يجب أن يكون الملف صورة',
            'image.mimes' => 'نوع الصورة يجب أن يكون: jpeg, png, jpg, gif',
            'image.max' => 'حجم الصورة لا يجب أن يتجاوز 2 ميجابايت',
            'price.numeric' => 'السعر يجب أن يكون رقم',
            'price.min' => 'السعر يجب أن يكون أكبر من أو يساوي 0',
            'gender.in' => 'الجنس يجب أن يكون: ذكر، أنثى، أو كليهما',
            'for.string' => 'المستهدف يجب أن يكون نص',
            'for.max' => 'المستهدف لا يجب أن يتجاوز 255 حرف',
            'goal.string' => 'الهدف يجب أن يكون نص',
            'goal.max' => 'الهدف لا يجب أن يتجاوز 255 حرف',
            'course_category_id.exists' => 'التصنيف غير موجود',
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

    public function authorize(): bool
    {
        return true;
    }
}
