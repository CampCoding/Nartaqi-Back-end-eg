<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateRoundRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'description' => 'nullable',
            'image' => 'nullable',
            'price' => 'required|numeric|min:0',
            'start_date' => 'nullable',
            'end_date' => 'nullable',
            'gender' => 'nullable|in:male,female,both',
            'for' => 'required|string|max:255',
            'goal' => 'required|string',
            'course_category_id' => 'required|exists:course_categories,id',
            'active' => 'required|integer|in:0,1',
            'teacher_id' => 'nullable',
            'category_part_id' => 'required|exists:category_parts,id',
            'source' => 'required|integer|in:0,1',
            'round_type' => 'sometimes|in:course,exams_only',
            'round_road_map_book' => 'nullable',
            'round_book' => 'nullable',
            'free' => 'required|integer|in:0,1',
            'capacity' => 'required|integer|min:0',
            'time_show' => 'nullable|string|max:255',
            'have_certificate' => 'required|integer|in:0,1',
            'category_part_free_id' => 'nullable|exists:category_parts_for_free,id',
            'in_store' => 'nullable',


        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الجولة مطلوب',
            'name.string' => 'اسم الجولة يجب أن يكون نص',
            'name.max' => 'اسم الجولة لا يجب أن يتجاوز 255 حرف',


            'price.required' => 'السعر مطلوب',
            'price.numeric' => 'السعر يجب أن يكون رقم',
            'price.min' => 'السعر يجب أن يكون أكبر من أو يساوي 0',
            'gender.required' => 'الجنس مطلوب',
            'gender.in' => 'الجنس يجب أن يكون: ذكر، أنثى، أو كليهما',
            'for.required' => 'المستهدف مطلوب',
            'for.string' => 'المستهدف يجب أن يكون نص',
            'for.max' => 'المستهدف لا يجب أن يتجاوز 255 حرف',
            'goal.required' => 'الهدف مطلوب',
            'goal.string' => 'الهدف يجب أن يكون نص',
            'goal.max' => 'الهدف لا يجب أن يتجاوز 255 حرف',
            'course_category_id.required' => 'التصنيف مطلوب',
            'course_category_id.exists' => 'التصنيف غير موجود',
            'active.required' => 'الحالة مطلوب',
            'active.integer' => 'الحالة يجب أن يكون صحيح أو خطأ',
            'active.in' => 'الحالة يجب أن يكون: 0 أو 1',
            'capacity.required' => 'السعة مطلوبة',
            'capacity.integer' => 'السعة يجب أن يكون رقم',
            'capacity.min' => 'السعة يجب أن يكون أكبر من أو يساوي 0',

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
