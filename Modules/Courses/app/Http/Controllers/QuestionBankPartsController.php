<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Modules\Courses\Models\QuestionBankPartsModel;

class QuestionBankPartsController extends Controller
{
    public function getQuestionBankParts(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $data = QuestionBankPartsModel::paginate($perPage);
        return res_data($data, 'success', 200);
    }

    public function addQuestionBankParts(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $part = QuestionBankPartsModel::create($data);
        return res_data($part, 'success', 200);
    }

    public function editQuestionBankParts(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'name' => 'required|string|max:255',
        ]);

        $part = QuestionBankPartsModel::find($data['id']);
        if (!$part) {
            return res_data('الجزء غير موجود', 'failed', 402);
        }

        $part->update($data);
        return res_data($part, 'success', 200);
    }

    public function deleteQuestionBankParts(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
        ]);

        $part = QuestionBankPartsModel::find($data['id']);
        if (!$part) {
            return res_data('الجزء غير موجود', 'failed', 402);
        }

        $part->delete();
        return res_data('success', 'success', 200);
    }
}
