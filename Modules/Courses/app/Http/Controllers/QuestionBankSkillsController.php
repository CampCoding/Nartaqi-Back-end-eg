<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\QuestionBankBranchesModel;
use Modules\Courses\Models\QuestionBankSkillsModel;

class QuestionBankSkillsController extends Controller
{
    public function getQuestionBankSkills(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $data = QuestionBankSkillsModel::with('branch')->paginate($perPage);
        return res_data($data, 'success', 200);
    }

    public function getQuestionBankSkillsByBranchId(Request $request)
    {
        $data = $request->validate([
            'question_bank_branch_id' => 'required|integer',
        ]);

        $branch = QuestionBankBranchesModel::find($data['question_bank_branch_id']);
        if (!$branch) {
            return res_data('الفرع غير موجود', 'failed', 402);
        }

        $skills = QuestionBankSkillsModel::where('question_bank_branch_id', $data['question_bank_branch_id'])->get();
        return res_data($skills, 'success', 200);
    }

    public function addQuestionBankSkills(Request $request)
    {
        $data = $request->validate([
            'question_bank_branch_id' => 'required|integer',
            'name' => 'required|string|max:255',
        ]);

        $branch = QuestionBankBranchesModel::find($data['question_bank_branch_id']);
        if (!$branch) {
            return res_data('الفرع غير موجود', 'failed', 402);
        }

        $skill = QuestionBankSkillsModel::create($data);
        return res_data($skill, 'success', 200);
    }

    public function editQuestionBankSkills(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'question_bank_branch_id' => 'required|integer',
            'name' => 'required|string|max:255',
        ]);

        $skill = QuestionBankSkillsModel::find($data['id']);
        if (!$skill) {
            return res_data('المهارة غير موجودة', 'failed', 402);
        }

        $branch = QuestionBankBranchesModel::find($data['question_bank_branch_id']);
        if (!$branch) {
            return res_data('الفرع غير موجود', 'failed', 402);
        }

        $skill->update($data);
        return res_data($skill, 'success', 200);
    }

    public function deleteQuestionBankSkills(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
        ]);

        $skill = QuestionBankSkillsModel::find($data['id']);
        if (!$skill) {
            return res_data('المهارة غير موجودة', 'failed', 402);
        }

        $skill->delete();
        return res_data('success', 'success', 200);
    }
}
