<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\QuestionBankPartsModel;
use Modules\Courses\Models\QuestionBankBranchesModel;

class QuestionBankBranchesController extends Controller
{
    public function getQuestionBankBranches(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $data = QuestionBankBranchesModel::with('part')->paginate($perPage);
        return res_data($data, 'success', 200);
    }

    public function getQuestionBankBranchesByPartId(Request $request)
    {
        $data = $request->validate([
            'question_bank_parts_id' => 'required|integer',
        ]);

        $part = QuestionBankPartsModel::find($data['question_bank_parts_id']);
        if (!$part) {
            return res_data('الجزء غير موجود', 'failed', 402);
        }

        $branches = QuestionBankBranchesModel::where('question_bank_parts_id', $data['question_bank_parts_id'])->get();
        return res_data($branches, 'success', 200);
    }

    public function addQuestionBankBranches(Request $request)
    {
        $data = $request->validate([
            'question_bank_parts_id' => 'required|integer',
            'name' => 'required|string|max:255',
        ]);

        $part = QuestionBankPartsModel::find($data['question_bank_parts_id']);
        if (!$part) {
            return res_data('الجزء غير موجود', 'failed', 402);
        }

        $branch = QuestionBankBranchesModel::create($data);
        return res_data($branch, 'success', 200);
    }

    public function editQuestionBankBranches(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'question_bank_parts_id' => 'required|integer',
            'name' => 'required|string|max:255',
        ]);

        $branch = QuestionBankBranchesModel::find($data['id']);
        if (!$branch) {
            return res_data('الفرع غير موجود', 'failed', 402);
        }

        $part = QuestionBankPartsModel::find($data['question_bank_parts_id']);
        if (!$part) {
            return res_data('الجزء غير موجود', 'failed', 402);
        }

        $branch->update($data);
        return res_data($branch, 'success', 200);
    }

    public function deleteQuestionBankBranches(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
        ]);

        $branch = QuestionBankBranchesModel::find($data['id']);
        if (!$branch) {
            return res_data('الفرع غير موجود', 'failed', 402);
        }

        $branch->delete();
        return res_data('success', 'success', 200);
    }
}
