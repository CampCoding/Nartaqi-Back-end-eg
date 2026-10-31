<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Courses\Models\AssignExamModel;
use Modules\Courses\Models\RoundExamSectionModel;

/**
 * Sections that group a round's whole-round (full_round) exams. The exams themselves live in
 * assign_exam_round; a section only gives them a heading via assign_exam_round.round_exam_section_id.
 */
class AdminRoundExamSectionController extends Controller
{
    public function getSectionsByRoundId(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
        ]);

        $sections = RoundExamSectionModel::where('round_id', $data['round_id'])
            ->withCount(['assignments as exams_count' => fn ($q) => $q->where('type', 'full_round')])
            ->orderBy('sort_number')
            ->orderBy('id')
            ->get();

        return res_data($sections, 'success', 200);
    }

    public function storeSection(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $data['sort_number'] = (int) RoundExamSectionModel::where('round_id', $data['round_id'])->max('sort_number') + 1;

        $section = RoundExamSectionModel::create($data);

        return res_data($section, 'تم إضافة القسم بنجاح', 201);
    }

    public function editSection(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:round_exam_sections,id',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $section = RoundExamSectionModel::find($data['id']);
        $section->update(collect($data)->except('id')->all());

        return res_data($section, 'تم تعديل القسم بنجاح', 200);
    }

    /**
     * Deletes the section only; its exams stay in the round and just become unsectioned.
     */
    public function deleteSection(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:round_exam_sections,id',
        ]);

        DB::transaction(function () use ($data) {
            AssignExamModel::where('round_exam_section_id', $data['id'])->update(['round_exam_section_id' => null]);
            RoundExamSectionModel::where('id', $data['id'])->delete();
        });

        return res_data('تم حذف القسم بنجاح، والاختبارات اللي كانت فيه بقت من غير قسم', 'success', 200);
    }

    public function sortSections(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
            'section_ids' => 'required|array|min:1',
            'section_ids.*' => 'integer|distinct',
        ]);

        $ids = array_map('intval', $data['section_ids']);
        $roundSectionIds = RoundExamSectionModel::where('round_id', $data['round_id'])->pluck('id')->all();

        if (array_diff($ids, $roundSectionIds)) {
            return res_data('بعض الأقسام مش تابعة للدورة دي', 'خطأ في التحقق من البيانات', 422);
        }

        // Sections the request didn't mention keep their relative order after the listed ones.
        $ordered = array_merge($ids, array_values(array_diff(
            RoundExamSectionModel::where('round_id', $data['round_id'])->orderBy('sort_number')->orderBy('id')->pluck('id')->all(),
            $ids
        )));

        DB::transaction(function () use ($ordered) {
            foreach ($ordered as $index => $id) {
                RoundExamSectionModel::where('id', $id)->update(['sort_number' => $index + 1]);
            }
        });

        return res_data(['round_id' => (int) $data['round_id'], 'section_ids' => $ordered], 'تم ترتيب الأقسام بنجاح', 200);
    }

    /**
     * Put already-assigned whole-round exams into a section (or out of any section with
     * round_exam_section_id = null). assign_ids are assign_exam_round ids (assign_data.id).
     */
    public function moveExamsToSection(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|exists:rounds,id',
            'round_exam_section_id' => 'present|nullable|integer',
            'assign_ids' => 'required|array|min:1',
            'assign_ids.*' => 'integer|distinct',
        ]);

        $sectionError = RoundExamSectionModel::assignmentError($data['round_exam_section_id'], 'full_round', $data['round_id']);
        if ($sectionError) {
            return res_data($sectionError, 'خطأ في التحقق من البيانات', 422);
        }

        $assignIds = array_map('intval', $data['assign_ids']);
        $valid = AssignExamModel::whereIn('id', $assignIds)
            ->where('type', 'full_round')
            ->where('lesson_or_round_id', $data['round_id'])
            ->count();

        if ($valid !== count($assignIds)) {
            return res_data('بعض الاختبارات مش اختبارات دورة كاملة (full_round) في الدورة دي', 'خطأ في التحقق من البيانات', 422);
        }

        AssignExamModel::whereIn('id', $assignIds)->update(['round_exam_section_id' => $data['round_exam_section_id']]);

        return res_data([
            'round_exam_section_id' => $data['round_exam_section_id'],
            'assign_ids' => $assignIds,
        ], 'تم نقل الاختبارات للقسم بنجاح', 200);
    }
}
