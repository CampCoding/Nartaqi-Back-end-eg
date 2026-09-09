<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlacementTestSuggestionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id'                => 'required|exists:placement_test_suggestion,id',
            'placement_test_id' => 'required|exists:placement_test,id',
            'from_score'        => 'required|integer',
            'to_score'          => 'required|integer',
            'message'           => 'nullable|string',
            'suggestion_round_id' => 'nullable|exists:rounds,id',
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
            'id.required'                => 'معرف الاقتراح مطلوب',
            'id.exists'                  => 'الاقتراح غير موجود',
            'placement_test_id.required' => 'معرف اختبار تحديد المستوى مطلوب',
            'placement_test_id.exists'   => 'اختبار تحديد المستوى غير موجود',
            'from_score.required'        => 'الدرجة الدنيا مطلوبة',
            'from_score.integer'         => 'يجب أن تكون الدرجة الدنيا رقماً',
            'to_score.required'          => 'الدرجة القصوى مطلوبة',
            'to_score.integer'           => 'يجب أن تكون الدرجة القصوى رقماً',
            'message.string'             => 'يجب أن تكون الرسالة نصاً',
            'suggestion_round_id.exists' => 'الدورة المختارة غير موجودة',
        ];
    }
}
